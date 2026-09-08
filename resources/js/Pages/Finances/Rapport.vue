<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Point 08 (rapports consolidés multidevises, 10/09/2026) : ce rapport
 * consolide désormais ce nœud et tous ses descendants (comme les autres
 * rapports consolidés de la plateforme), et n'additionne jamais deux
 * devises ensemble puisqu'aucune conversion de change n'existe dans
 * l'application : chaque devise rencontrée reçoit son propre bloc, avec
 * son propre détail et son propre solde.
 */
const props = defineProps({
    orgUnit: Object,
    month: String,
    devises: Array,
})

function formatAmount(value, currency) {
    return new Intl.NumberFormat('fr-FR').format(value) + ' ' + currency
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
                    Vue consolidée de ce niveau et de tous ses niveaux descendants. Aucune conversion de change n'existant dans l'application, chaque devise rencontrée est présentée dans son propre bloc, jamais mélangée à une autre.
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

            <div v-if="devises.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center animate-[fadeInUp_0.55s_ease-out_both]">
                <p class="text-sm font-medium text-white/70">Aucun mouvement enregistré ce mois-ci, sur ce niveau ou ses descendants.</p>
            </div>

            <div v-for="devise in devises" :key="devise.currency" class="space-y-6 animate-[fadeInUp_0.55s_ease-out_both]">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    <h2 class="text-sm font-semibold text-gold-soft/90 uppercase tracking-widest">{{ devise.currency }}</h2>
                    <span v-if="devise.accountingStandardLabel" class="text-xs text-white/40">· Détail par compte comptable ({{ devise.accountingStandardLabel }})</span>
                    <span v-else class="text-xs text-white/40">· Détail par nature de mouvement</span>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="glass-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-forest/90">Total encaissements</p>
                        <p class="mt-2 text-2xl font-serif text-white">{{ formatAmount(devise.totalEncaissements, devise.currency) }}</p>
                    </div>
                    <div class="glass-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-sanctuary-light">Total décaissements</p>
                        <p class="mt-2 text-2xl font-serif text-white">{{ formatAmount(devise.totalDecaissements, devise.currency) }}</p>
                    </div>
                    <div class="glass-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-white/50">Solde de trésorerie</p>
                        <p class="mt-2 text-2xl font-serif text-white">{{ formatAmount(devise.solde, devise.currency) }}</p>
                    </div>
                </div>

                <section class="glass-panel rounded-3xl p-6">
                    <h3 class="mb-4 text-xs font-semibold text-gold-soft/80 uppercase tracking-widest">Encaissements</h3>
                    <div v-if="devise.encaissements.length === 0" class="text-sm text-white/40">Aucun encaissement ce mois-ci.</div>
                    <table v-else class="w-full text-sm">
                        <tbody>
                            <tr v-for="line in devise.encaissements" :key="line.account_code || line.account_label" class="border-b border-white/10 last:border-0">
                                <td v-if="devise.accountingStandardLabel" class="py-2 text-white/45">{{ line.account_code }}</td>
                                <td class="py-2 text-white/80">{{ line.account_label }}</td>
                                <td class="py-2 text-right font-medium text-white">{{ formatAmount(line.total, devise.currency) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="glass-panel rounded-3xl p-6">
                    <h3 class="mb-4 text-xs font-semibold text-gold-soft/80 uppercase tracking-widest">Décaissements</h3>
                    <div v-if="devise.decaissements.length === 0" class="text-sm text-white/40">Aucun décaissement ce mois-ci.</div>
                    <table v-else class="w-full text-sm">
                        <tbody>
                            <tr v-for="line in devise.decaissements" :key="line.account_code || line.account_label" class="border-b border-white/10 last:border-0">
                                <td v-if="devise.accountingStandardLabel" class="py-2 text-white/45">{{ line.account_code }}</td>
                                <td class="py-2 text-white/80">{{ line.account_label }}</td>
                                <td class="py-2 text-right font-medium text-white">{{ formatAmount(line.total, devise.currency) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>

            <p class="text-xs text-white/35">Ce rapport est calculé automatiquement depuis les mouvements enregistrés, jamais ressaisi séparément.</p>
        </div>
    </AppLayout>
</template>
