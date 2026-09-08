<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel (memes props,
 * memes routes, meme logique) - seul l'habillage passe du blanc/ardoise
 * a la coquille sombre en verre depoli.
 */
const props = defineProps({
    orgUnit: Object,
    transactions: Array,
    month: String,
    totals: Object,
    currency: String,
    accountingStandardLabel: String,
})

function typeLabel(type) {
    return {
        dime: 'Dîme',
        offrande: 'Offrande',
        action_de_grace: 'Action de grâce',
        don: 'Don',
        depense: 'Dépense',
    }[type] ?? type
}

function paymentMethodLabel(method) {
    return {
        especes: 'Espèces',
        cheque: 'Chèque',
        virement: 'Virement bancaire',
        mobile_money: 'Mobile Money',
        autre: 'Autre',
    }[method] ?? method
}

// Point 18 : la devise vient du pays de l'entite (via le controleur), plus
// jamais un "FCFA" fige quel que soit le pays reel du mouvement.
function formatAmount(value, currency) {
    return new Intl.NumberFormat('fr-FR').format(value) + ' ' + (currency || props.currency)
}

function formatDate(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function changeMonth(event) {
    router.get(`/org-units/${props.orgUnit.id}/finances`, { mois: event.target.value })
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Finances
                </p>
                <h1 class="font-serif text-3xl text-white mt-2">Dîmes, offrandes et dépenses</h1>
                <p class="text-sm text-white/55 mt-2 max-w-2xl">Enregistrées et consultables mois par mois, sans jamais être ressaisies ailleurs.</p>
                <p v-if="!accountingStandardLabel" class="mt-2 text-xs text-gold-soft/90">
                    Aucune norme comptable n'est encore configurée pour ce pays : les mouvements sont enregistrés sans compte comptable, ce qui n'empêche pas leur saisie.
                </p>
            </div>
        </template>

        <div class="space-y-8">
            <div class="flex flex-wrap items-center justify-between gap-4 animate-[fadeInUp_0.55s_ease-out_both]">
                <input
                    type="month"
                    :value="month"
                    @change="changeMonth"
                    class="bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"
                />
                <Link
                    :href="`/org-units/${orgUnit.id}/finances/nouveau`"
                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Ajouter un mouvement
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 animate-[fadeInUp_0.6s_ease-out_both]">
                <div class="glass-panel rounded-2xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-forest/90">Encaissements</p>
                    <p class="mt-2 text-2xl font-serif text-white">{{ formatAmount(totals.encaissements) }}</p>
                </div>
                <div class="glass-panel rounded-2xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-sanctuary-light">Décaissements</p>
                    <p class="mt-2 text-2xl font-serif text-white">{{ formatAmount(totals.decaissements) }}</p>
                </div>
                <div class="glass-panel rounded-2xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/50">Solde du mois</p>
                    <p class="mt-2 text-2xl font-serif text-white">{{ formatAmount(totals.solde) }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 text-sm animate-[fadeInUp_0.65s_ease-out_both]">
                <Link
                    :href="`/org-units/${orgUnit.id}/finances-rapport?mois=${month}`"
                    class="inline-flex items-center gap-1.5 glass-panel-light rounded-full px-4 py-2 font-medium text-white/80 hover:border-gold/40 hover:text-gold-soft transition"
                >
                    Rapport financier du mois
                </Link>
                <Link
                    :href="`/org-units/${orgUnit.id}/rapport-activites?mois=${month}`"
                    class="inline-flex items-center gap-1.5 glass-panel-light rounded-full px-4 py-2 font-medium text-white/80 hover:border-gold/40 hover:text-gold-soft transition"
                >
                    Rapport d'activités du mois
                </Link>
            </div>

            <div v-if="transactions.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/5 text-white/40">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-white/70">Aucun mouvement enregistré ce mois-ci.</p>
                <p class="mt-1 text-sm text-white/40">Ajoute une dîme, une offrande ou une dépense pour commencer.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(transaction, i) in transactions"
                    :key="transaction.id"
                    class="flex items-center gap-4 glass-panel rounded-2xl p-5 hover:border-white/20 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                    :style="{ animationDelay: `${Math.min(i, 10) * 40}ms` }"
                >
                    <span
                        :class="[
                            'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl',
                            transaction.nature === 'encaissement' ? 'bg-forest/15 text-forest' : 'bg-sanctuary/15 text-sanctuary-light',
                        ]"
                    >
                        <svg v-if="transaction.nature === 'encaissement'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5v-15m0 0-6.75 6.75M12 4.5l6.75 6.75" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m0 0 6.75-6.75M12 19.5l-6.75-6.75" />
                        </svg>
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate font-semibold text-white text-[15px]">
                                {{ typeLabel(transaction.type) }}<span v-if="transaction.account_label"> · {{ transaction.account_label }}</span>
                            </p>
                            <p :class="['flex-shrink-0 font-semibold', transaction.nature === 'encaissement' ? 'text-forest' : 'text-sanctuary-light']">
                                {{ transaction.nature === 'encaissement' ? '+' : '-' }}{{ formatAmount(transaction.amount, transaction.currency) }}
                            </p>
                        </div>
                        <p class="mt-1 text-sm text-white/45">
                            {{ formatDate(transaction.transaction_date) }}
                            <span v-if="transaction.counterparty"> · {{ transaction.counterparty }}</span>
                            <span v-if="transaction.payment_method"> · {{ paymentMethodLabel(transaction.payment_method) }}</span>
                        </p>
                    </div>

                    <Link
                        :href="`/org-units/${orgUnit.id}/finances/${transaction.id}/modifier`"
                        class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-white/50 transition hover:bg-white/10 hover:text-white"
                    >
                        Modifier
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
