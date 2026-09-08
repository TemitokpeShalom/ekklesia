<script setup>
import { router } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
    date: String,
    parCategorie: Object,
    currency: String,
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

function changeDate(event) {
    router.get(`/org-units/${props.orgUnit.id}/inventaire-rapport`, { date: event.target.value })
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/inventaire`" back-label="Retour à l'inventaire">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Inventaire des biens
                </p>
                <h1 class="font-serif text-3xl text-white mt-2">Fiche d'inventaire consolidée</h1>
                <p class="text-sm text-white/55 mt-2 max-w-2xl">
                    Biens propres de {{ orgUnit.name }} et de tous les niveaux qu'il regroupe, à la date choisie.
                </p>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-8">
            <div class="flex justify-end animate-[fadeInUp_0.5s_ease-out_both]">
                <input
                    type="date"
                    :value="date"
                    @change="changeDate"
                    class="bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"
                />
            </div>

            <div class="glass-panel rounded-2xl p-5 animate-[fadeInUp_0.55s_ease-out_both]">
                <p class="text-xs font-semibold uppercase tracking-widest text-white/50">Valeur totale du patrimoine</p>
                <p class="mt-2 text-2xl font-serif text-white">{{ formatAmount(totalGeneral) }}</p>
            </div>

            <section
                v-for="(cat, i) in categories"
                :key="cat.key"
                class="glass-panel rounded-3xl p-6 animate-[fadeInUp_0.5s_ease-out_both]"
                :style="{ animationDelay: `${0.1 + i * 0.05}s` }"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest">{{ cat.label }}</h3>
                    <p class="text-sm font-semibold text-white">{{ formatAmount(groupFor(cat.key).total) }}</p>
                </div>
                <div v-if="groupFor(cat.key).items.length === 0" class="text-sm text-white/40">Aucun bien dans cette catégorie.</div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10 text-left text-xs uppercase tracking-widest text-white/40">
                                <th class="py-2 pr-3 font-medium">Code</th>
                                <th class="py-2 pr-3 font-medium">Bien</th>
                                <th class="py-2 pr-3 font-medium">Niveau</th>
                                <th class="py-2 pr-3 font-medium">Provenance</th>
                                <th class="py-2 pr-3 font-medium">Acquis le</th>
                                <th class="py-2 pr-3 font-medium">État</th>
                                <th class="py-2 text-right font-medium">Valeur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in groupFor(cat.key).items" :key="item.id" class="border-b border-white/10 last:border-0">
                                <td class="py-2 pr-3 font-mono text-xs text-white/40">{{ item.code }}</td>
                                <td class="py-2 pr-3 text-white/80">{{ item.label }} <span class="text-white/40">×{{ item.quantity }}</span></td>
                                <td class="py-2 pr-3 text-white/45">{{ item.org_unit?.name }}</td>
                                <td class="py-2 pr-3 text-white/45">{{ provenanceLabel(item.provenance) }}</td>
                                <td class="py-2 pr-3 text-white/45">{{ formatDate(item.acquisition_date) }}</td>
                                <td class="py-2 pr-3 text-white/45">{{ conditionLabel(item.condition) }}</td>
                                <td class="py-2 text-right font-medium text-white">{{ formatAmount(item.acquisition_value) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <p class="text-xs text-white/35">Cette fiche est calculée automatiquement depuis le registre des biens, jamais ressaisie séparément.</p>
        </div>
    </AppLayout>
</template>
