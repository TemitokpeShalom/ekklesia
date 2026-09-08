<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
    month: String,
    encaissements: Array,
    decaissements: Array,
    totalEncaissements: [Number, String],
    totalDecaissements: [Number, String],
    solde: [Number, String],
    currency: String,
    accountingStandardLabel: String,
})

function formatAmount(value) {
    return new Intl.NumberFormat('fr-FR').format(value) + ' ' + props.currency
}

function monthLabel(value) {
    const [year, month] = value.split('-')
    return new Date(year, month - 1, 1).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
}

function changeMonth(event) {
    router.get(`/org-units/${props.orgUnit.id}/finances-rapport`, { mois: event.target.value })
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/finances`" back-label="Retour aux finances">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Finances
                </p>
                <h1 class="font-serif text-3xl text-white mt-2 capitalize">Rapport financier · {{ monthLabel(month) }}</h1>
                <p class="text-sm text-white/55 mt-2 max-w-2xl">
                    {{ accountingStandardLabel ? `Détail par compte comptable (${accountingStandardLabel}).` : "Norme comptable non encore configurée pour ce pays : détail par nature de mouvement." }}
                </p>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-8">
            <div class="flex justify-end animate-[fadeInUp_0.5s_ease-out_both]">
                <input
                    type="month"
                    :value="month"
                    @change="changeMonth"
                    class="bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 animate-[fadeInUp_0.55s_ease-out_both]">
                <div class="glass-panel rounded-2xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-forest/90">Total encaissements</p>
                    <p class="mt-2 text-2xl font-serif text-white">{{ formatAmount(totalEncaissements) }}</p>
                </div>
                <div class="glass-panel rounded-2xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-sanctuary-light">Total décaissements</p>
                    <p class="mt-2 text-2xl font-serif text-white">{{ formatAmount(totalDecaissements) }}</p>
                </div>
                <div class="glass-panel rounded-2xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/50">Solde de trésorerie</p>
                    <p class="mt-2 text-2xl font-serif text-white">{{ formatAmount(solde) }}</p>
                </div>
            </div>

            <section class="glass-panel rounded-3xl p-6 animate-[fadeInUp_0.6s_ease-out_both]">
                <h3 class="mb-4 text-xs font-semibold text-gold-soft/80 uppercase tracking-widest">Encaissements</h3>
                <div v-if="encaissements.length === 0" class="text-sm text-white/40">Aucun encaissement ce mois-ci.</div>
                <table v-else class="w-full text-sm">
                    <tbody>
                        <tr v-for="line in encaissements" :key="line.account_code || line.account_label" class="border-b border-white/10 last:border-0">
                            <td v-if="accountingStandardLabel" class="py-2 text-white/45">{{ line.account_code }}</td>
                            <td class="py-2 text-white/80">{{ line.account_label }}</td>
                            <td class="py-2 text-right font-medium text-white">{{ formatAmount(line.total) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="glass-panel rounded-3xl p-6 animate-[fadeInUp_0.65s_ease-out_both]">
                <h3 class="mb-4 text-xs font-semibold text-gold-soft/80 uppercase tracking-widest">Décaissements</h3>
                <div v-if="decaissements.length === 0" class="text-sm text-white/40">Aucun décaissement ce mois-ci.</div>
                <table v-else class="w-full text-sm">
                    <tbody>
                        <tr v-for="line in decaissements" :key="line.account_code || line.account_label" class="border-b border-white/10 last:border-0">
                            <td v-if="accountingStandardLabel" class="py-2 text-white/45">{{ line.account_code }}</td>
                            <td class="py-2 text-white/80">{{ line.account_label }}</td>
                            <td class="py-2 text-right font-medium text-white">{{ formatAmount(line.total) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <p class="text-xs text-white/35">Ce rapport est calculé automatiquement depuis les mouvements enregistrés, jamais ressaisi séparément.</p>
        </div>
    </AppLayout>
</template>
