<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Tableau de bord technique Oikonema (2026-09-13, complete le meme jour) :
 * vue d'ensemble des abonnements de TOUS les ministeres de la plateforme -
 * reserve a l'equipe technique (voir EnsureTechnicalStaff). Retour du
 * ministere (13/09, apres la premiere version) : possibilite de regler
 * l'abonnement d'un ministere a la main ("honorer"/"exonérer" sans passer
 * par FedaPay/crypto) - voir TechniqueController::updateSubscription.
 * "Exonerer durablement" = statut actif + echeance laissee vide (une
 * echeance nulle est deja traitee comme illimitee par
 * Ministry::subscriptionActive(), aucun nouveau statut invente).
 */
defineProps({
    ministries: Array,
    plans: Array,
    summary: Object,
})

function statusLabel(status, onTrial) {
    if (onTrial) return 'Essai'
    if (status === 'active') return 'Actif'
    if (status === 'expiree') return 'Expiré'
    return status
}

function statusClass(status, onTrial) {
    if (onTrial) return 'bg-gold/10 text-gold-dark'
    if (status === 'active') return 'bg-forest/10 text-forest'
    if (status === 'expiree') return 'bg-rose-50 text-rose-700'
    return 'bg-graphite/10 text-graphite/70'
}

function formatDate(iso) {
    if (!iso) return 'Sans échéance'
    return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}

function toDateInput(iso) {
    return iso ? iso.slice(0, 10) : ''
}

// Édition de l'abonnement en place, ligne par ligne (retour du 13/09) :
// pas de page ni de fenêtre à part, juste la ligne du ministère concerné
// qui s'ouvre pour montrer le petit formulaire, le temps de la modifier.
const editingId = ref(null)
const form = useForm({
    plan_id: '',
    subscription_status: '',
    trial_ends_at: '',
    current_period_ends_at: '',
})

function startEdit(m) {
    editingId.value = m.id
    form.clearErrors()
    form.plan_id = m.plan_id || ''
    form.subscription_status = m.subscription_status
    form.trial_ends_at = toDateInput(m.trial_ends_at)
    form.current_period_ends_at = toDateInput(m.current_period_ends_at)
}

function cancelEdit() {
    editingId.value = null
}

function save(m) {
    form.put(`/technique/ministeres/${m.id}/abonnement`, {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null },
    })
}
</script>

<template>
    <AppLayout>
        <template #title>
            <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2 mb-3">
                <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                Espace technique
            </p>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <h1 class="font-serif text-4xl font-bold text-graphite">Tous les ministères</h1>
                <nav class="flex items-center gap-5 text-sm font-medium">
                    <Link href="/technique/signalements" class="text-azure hover:underline">
                        Signalements techniques
                        <span v-if="summary.nouveaux_signalements" class="inline-flex items-center justify-center ml-1 min-w-[1.25rem] h-5 px-1.5 rounded-full bg-sanctuary text-white text-xs">{{ summary.nouveaux_signalements }}</span>
                    </Link>
                    <Link href="/technique/equipe" class="text-azure hover:underline">Équipe technique</Link>
                </nav>
            </div>
        </template>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-10">
            <div class="glass-panel-light rounded-2xl px-5 py-4">
                <p class="text-2xl font-serif text-graphite">{{ summary.total }}</p>
                <p class="text-sm text-graphite/60">Ministères au total</p>
            </div>
            <div class="glass-panel-light rounded-2xl px-5 py-4">
                <p class="text-2xl font-serif text-gold-dark">{{ summary.en_essai }}</p>
                <p class="text-sm text-graphite/60">En essai</p>
            </div>
            <div class="glass-panel-light rounded-2xl px-5 py-4">
                <p class="text-2xl font-serif text-forest">{{ summary.actifs }}</p>
                <p class="text-sm text-graphite/60">Abonnement actif</p>
            </div>
            <div class="glass-panel-light rounded-2xl px-5 py-4">
                <p class="text-2xl font-serif text-rose-700">{{ summary.expires }}</p>
                <p class="text-sm text-graphite/60">Expirés</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-graphite/10">
            <table class="w-full text-sm">
                <thead class="bg-graphite/5 text-graphite/60 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Ministère</th>
                        <th class="px-4 py-3 font-medium">Offre</th>
                        <th class="px-4 py-3 font-medium">Statut</th>
                        <th class="px-4 py-3 font-medium">Échéance</th>
                        <th class="px-4 py-3 font-medium">Entités rattachées</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-graphite/10">
                    <template v-for="m in ministries" :key="m.id">
                        <tr>
                            <td class="px-4 py-3">
                                <p class="text-graphite font-medium">{{ m.name }}</p>
                                <p v-if="m.short_code" class="text-graphite/50 text-xs">{{ m.short_code }}</p>
                            </td>
                            <td class="px-4 py-3 text-graphite/80">{{ m.plan_name || 'Découverte' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block rounded-full px-3 py-1 text-xs font-medium" :class="statusClass(m.subscription_status, m.on_trial)">
                                    {{ statusLabel(m.subscription_status, m.on_trial) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-graphite/70">{{ formatDate(m.on_trial ? m.trial_ends_at : m.current_period_ends_at) }}</td>
                            <td class="px-4 py-3 text-graphite/70">{{ m.org_units_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="editingId !== m.id" type="button" @click="startEdit(m)" class="text-sm text-azure hover:underline font-medium">Modifier</button>
                            </td>
                        </tr>
                        <tr v-if="editingId === m.id" class="bg-graphite/[0.03]">
                            <td colspan="6" class="px-4 py-5">
                                <form @submit.prevent="save(m)" class="flex flex-wrap items-end gap-4">
                                    <label class="text-sm">
                                        <span class="block text-graphite/70 mb-1">Offre</span>
                                        <select v-model="form.plan_id" class="rounded-xl border border-graphite/15 px-3 py-2 text-sm min-w-[180px]">
                                            <option value="">Découverte (par défaut)</option>
                                            <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                                        </select>
                                    </label>
                                    <label class="text-sm">
                                        <span class="block text-graphite/70 mb-1">Statut</span>
                                        <select v-model="form.subscription_status" class="rounded-xl border border-graphite/15 px-3 py-2 text-sm">
                                            <option value="essai">Essai</option>
                                            <option value="active">Actif</option>
                                            <option value="expiree">Expiré</option>
                                        </select>
                                    </label>
                                    <label class="text-sm">
                                        <span class="block text-graphite/70 mb-1">Fin d'essai</span>
                                        <input v-model="form.trial_ends_at" type="date" class="rounded-xl border border-graphite/15 px-3 py-2 text-sm" />
                                    </label>
                                    <label class="text-sm">
                                        <span class="block text-graphite/70 mb-1">Échéance (actif)</span>
                                        <input v-model="form.current_period_ends_at" type="date" class="rounded-xl border border-graphite/15 px-3 py-2 text-sm" />
                                        <span class="block text-graphite/45 text-xs mt-1 max-w-[220px]">Laisser vide + statut actif = accès illimité (pour exonérer durablement).</span>
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <button type="submit" :disabled="form.processing" class="rounded-full bg-gradient-to-r from-azure to-azure-dark text-white px-5 py-2 text-sm font-medium shadow-azure/20 shadow-lg disabled:opacity-60">Enregistrer</button>
                                        <button type="button" @click="cancelEdit" class="text-sm text-graphite/60 hover:underline">Annuler</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="!ministries.length">
                        <td colspan="6" class="px-4 py-6 text-center text-graphite/55">Aucun ministère pour l'instant.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
