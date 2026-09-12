<script setup>
import { router } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import MinistryLetterhead from '@/Components/MinistryLetterhead.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 *
 * Chantier "module Inventaire" (2026-09-12, retour du ministere : "a la
 * fin de chaque annee, on nous demande souvent de faire la liste des
 * inventaires et d'envoyer... c'est un bouton qui manque et c'est un
 * document qui manque") :
 * - en-tete du ministere + bloc "position" (meme convention que
 *   Finances/Activités) ajoutés ici, sur le rapport "vivant" ;
 * - colonne "Observation" ajoutée (déjà enregistrée par bien, jamais
 *   affichée jusqu'ici) ;
 * - bouton "Valider et archiver" : envoie ce bilan (à la date choisie,
 *   normalement le 31 décembre) dans Documents › Rapports, exactement
 *   comme pour les Finances - c'est le bouton qui manquait.
 */
const props = defineProps({
    orgUnit: Object,
    date: String,
    year: Number,
    parCategorie: Object,
    currency: String,
    ministry: Object,
    ancestry: Array,
    pastorName: String,
    validation: Object,
    canManage: Boolean,
})

const categories = [
    { key: 'immobilier', label: 'Biens immobiliers' },
    { key: 'mobilier', label: 'Biens mobiliers' },
]

function groupFor(key) {
    return props.parCategorie?.[key] ?? { items: [], total: 0 }
}

const totalGeneral = computed(() => {
    return categories.reduce((sum, c) => sum + (groupFor(c.key).total || 0), 0)
})

const isValidated = computed(() => !!props.validation)

function provenanceLabel(value) {
    return {
        don: 'Don',
        achat_caisse: 'Achat sur caisse',
        achat_offrande: 'Achat sur offrande',
        subvention: 'Subvention',
        legs: 'Legs',
        construction: 'Construction',
    }[value] ?? value
}

function conditionLabel(value) {
    return {
        fonctionnel: 'Fonctionnel',
        a_surveiller: 'À surveiller',
        hors_service: 'Hors service',
    }[value] ?? value
}

function formatAmount(value) {
    return new Intl.NumberFormat('fr-FR').format(value || 0) + ' ' + props.currency
}

function formatDate(value) {
    if (!value) return '-'
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function validatedLabel(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function changeDate(event) {
    router.get(`/org-units/${props.orgUnit.id}/inventaire-rapport`, { date: event.target.value })
}

function validate() {
    router.post(`/org-units/${props.orgUnit.id}/inventaire-rapport/valider`, { year: props.year })
}

function unlock() {
    if (!confirm("Déverrouiller cette fiche d'inventaire ? Elle ne sera plus visible dans les archives tant qu'elle ne sera pas revalidée.")) return
    router.post(`/org-units/${props.orgUnit.id}/inventaire-rapport/deverrouiller`, { year: props.year })
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/inventaire`" back-label="Retour à l'inventaire">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-azure/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-azure/40"></span>
                    Inventaire des biens
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">Fiche d'inventaire consolidée</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">
                    Biens propres de {{ orgUnit.name }} et de tous les niveaux qu'il regroupe, à la date choisie. Choisissez le 31 décembre d'une année pour produire le document de fin d'année à archiver.
                </p>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-8">
            <MinistryLetterhead :ministry="ministry" />

            <!-- Bloc "position" (retour du ministere, 2026-09-12), meme convention que Finances/Activités. -->
            <div v-if="ancestry?.length || pastorName" class="text-center -mt-2 animate-[fadeInUp_0.52s_ease-out_both]">
                <p v-if="ancestry?.length" class="text-xs text-graphite/62">
                    <span v-for="(a, i) in ancestry" :key="i">{{ a.name }}<span class="text-graphite/40"> ({{ a.label }})</span><span v-if="i < ancestry.length - 1"> › </span></span>
                </p>
                <p v-if="pastorName" class="text-xs text-graphite/62 mt-0.5">Pasteur : {{ pastorName }}</p>
            </div>

            <div class="flex items-center justify-between gap-4 flex-wrap animate-[fadeInUp_0.5s_ease-out_both]">
                <div v-if="isValidated" class="text-sm text-forest bg-forest/10 border border-forest/30 rounded-xl px-4 py-2.5">
                    Validée et archivée le {{ validatedLabel(validation.validated_at) }}<span v-if="validation.validator_name"> par {{ validation.validator_name }}</span>.
                    <a :href="`/org-units/${orgUnit.id}/documents/rapports/inventaire/${year}`" class="underline font-medium">Voir dans les archives</a>
                </div>
                <div v-else class="text-sm text-graphite/60">Fiche {{ year }} non validée — pas encore archivée dans Documents › Rapports.</div>

                <input
                    type="date"
                    :value="date"
                    @change="changeDate"
                    class="bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"
                />
            </div>

            <div v-if="canManage" class="flex justify-end gap-2 animate-[fadeInUp_0.52s_ease-out_both] print:hidden">
                <button v-if="!isValidated" type="button" @click="validate"
                    class="inline-flex items-center gap-1.5 bg-forest hover:bg-forest/90 transition-colors text-white rounded-xl px-4 py-2 text-sm font-semibold">
                    Valider et archiver {{ year }}
                </button>
                <button v-else type="button" @click="unlock"
                    class="inline-flex items-center gap-1.5 bg-graphite/5 hover:bg-graphite/10 transition-colors text-graphite/80 rounded-xl px-4 py-2 text-sm font-semibold">
                    Déverrouiller
                </button>
            </div>

            <div class="glass-panel rounded-2xl p-5 animate-[fadeInUp_0.55s_ease-out_both]">
                <p class="text-xs font-semibold uppercase tracking-widest text-graphite/68">Valeur totale du patrimoine</p>
                <p class="mt-2 text-2xl font-serif text-graphite">{{ formatAmount(totalGeneral) }}</p>
            </div>

            <section
                v-for="(cat, i) in categories"
                :key="cat.key"
                class="glass-panel rounded-3xl p-6 animate-[fadeInUp_0.5s_ease-out_both]"
                :style="{ animationDelay: `${0.1 + i * 0.05}s` }"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-azure uppercase tracking-widest">{{ cat.label }}</h3>
                    <p class="text-sm font-semibold text-graphite">{{ formatAmount(groupFor(cat.key).total) }}</p>
                </div>
                <div v-if="groupFor(cat.key).items.length === 0" class="text-sm text-graphite/62">Aucun bien dans cette catégorie.</div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-graphite/10 text-left text-xs uppercase tracking-widest text-graphite/62">
                                <th class="py-2 pr-3 font-medium">Code</th>
                                <th class="py-2 pr-3 font-medium">Bien</th>
                                <th class="py-2 pr-3 font-medium">Niveau</th>
                                <th class="py-2 pr-3 font-medium">Provenance</th>
                                <th class="py-2 pr-3 font-medium">Acquis le</th>
                                <th class="py-2 pr-3 font-medium">État</th>
                                <th class="py-2 pr-3 font-medium">Observation</th>
                                <th class="py-2 text-right font-medium">Valeur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in groupFor(cat.key).items" :key="item.id" class="border-b border-graphite/10 last:border-0">
                                <td class="py-2 pr-3 font-mono text-xs text-graphite/62">{{ item.code }}</td>
                                <td class="py-2 pr-3 text-graphite/87">{{ item.label }} <span class="text-graphite/62">×{{ item.quantity }}</span></td>
                                <td class="py-2 pr-3 text-graphite/65">{{ item.org_unit?.name }}</td>
                                <td class="py-2 pr-3 text-graphite/65">{{ provenanceLabel(item.provenance) }}</td>
                                <td class="py-2 pr-3 text-graphite/65">{{ formatDate(item.acquisition_date) }}</td>
                                <td class="py-2 pr-3 text-graphite/65">{{ conditionLabel(item.condition) }}</td>
                                <td class="py-2 pr-3 text-graphite/58">{{ item.observation || '—' }}</td>
                                <td class="py-2 text-right font-medium text-graphite">{{ formatAmount(item.acquisition_value) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <p class="text-xs text-graphite/58">Cette fiche est calculée automatiquement depuis le registre des biens, jamais ressaisie séparément. Le code d'identification de chaque bien est généré automatiquement et n'est jamais réattribué.</p>
        </div>
    </AppLayout>
</template>
