<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Tableau de bord technique Oikonema (2026-09-13, premiere livraison) :
 * vue d'ensemble des abonnements de TOUS les ministeres de la plateforme -
 * reserve a l'equipe technique (voir EnsureTechnicalStaff), jamais
 * accessible depuis un compte de ministere ordinaire. Le canal de
 * signalement des ministeres vers l'equipe technique arrivera dans une
 * prochaine livraison ; pour l'instant, cet ecran se limite aux abonnements
 * et a la gestion de l'equipe elle-meme.
 */
defineProps({
    ministries: Array,
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
                <Link href="/technique/equipe" class="inline-flex items-center gap-2 text-sm text-azure hover:underline font-medium">
                    Gérer l'équipe technique
                </Link>
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-graphite/10">
                    <tr v-for="m in ministries" :key="m.id">
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
                    </tr>
                    <tr v-if="!ministries.length">
                        <td colspan="5" class="px-4 py-6 text-center text-graphite/55">Aucun ministère pour l'instant.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
