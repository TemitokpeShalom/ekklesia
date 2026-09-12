<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel (memes props,
 * memes routes, meme logique) - seul l'habillage passe du blanc/ardoise
 * a la coquille sombre en verre depoli.
 *
 * Corrige le 2026-09-12 (retour du ministere, chantier "module Finances") :
 * boutons recolores en azur (comme tous les autres modules deja corriges) ;
 * "Rapport d'activités du mois" retire d'ici - ce rapport a deja sa propre
 * tuile independante sur le tableau de bord ("Rapport d'activités",
 * key: 'rapport'), ce lien-ci etait un doublon ; ajout du selecteur de
 * norme comptable ("il faut... choisir la norme comptable").
 */
const props = defineProps({
    orgUnit: Object,
    transactions: Array,
    month: String,
    totals: Object,
    currency: String,
    accountingStandardLabel: String,
    canManage: Boolean,
    accountingStandard: Object,
})

const standardMenuOpen = ref(false)
const standardForm = useForm({ standard: null })

function chooseStandard(code) {
    standardForm.standard = code
    standardForm.post(`/org-units/${props.orgUnit.id}/finances/norme-comptable`, {
        preserveScroll: true,
        onSuccess: () => { standardMenuOpen.value = false },
    })
}

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
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Finances
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">Dîmes, offrandes et dépenses</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">Enregistrées et consultables mois par mois, sans jamais être ressaisies ailleurs.</p>
                <p v-if="!accountingStandardLabel" class="mt-2 text-xs text-sanctuary/90">
                    Aucune norme comptable n'est encore configurée pour ce pays : les mouvements sont enregistrés sans compte comptable, ce qui n'empêche pas leur saisie.
                </p>

                <div v-if="canManage" class="relative mt-4 inline-block">
                    <button type="button" @click="standardMenuOpen = !standardMenuOpen"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-azure bg-azure/10 hover:bg-azure/15 border border-azure/20 rounded-full px-3.5 py-1.5 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.766.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.273-.807.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        Norme comptable : {{ accountingStandardLabel ?? 'aucune (universelle)' }}
                        <span v-if="accountingStandard?.isOverride" class="text-azure/70">(forcée)</span>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </button>

                    <button v-if="standardMenuOpen" type="button" tabindex="-1" aria-hidden="true"
                        @click="standardMenuOpen = false" class="fixed inset-0 z-10 cursor-default"></button>

                    <div v-if="standardMenuOpen"
                        class="absolute left-0 mt-2 w-80 z-20 rounded-2xl border border-graphite/10 bg-white shadow-card-hover py-2 animate-[fadeInUp_0.15s_ease-out_both]">
                        <p class="px-4 pt-1.5 pb-1.5 text-[11px] uppercase tracking-widest text-graphite/60 font-semibold">Choisir la norme comptable</p>
                        <button type="button" @click="chooseStandard(null)"
                            class="w-full text-left block px-4 py-2 hover:bg-graphite/5">
                            <p class="text-sm font-medium text-graphite">Détection automatique</p>
                            <p class="text-xs text-graphite/60">Selon le pays de cette entité</p>
                        </button>
                        <button v-for="option in accountingStandard.available" :key="option.code" type="button" @click="chooseStandard(option.code)"
                            class="w-full text-left block px-4 py-2 hover:bg-graphite/5">
                            <p class="text-sm font-medium text-graphite">{{ option.label }}</p>
                            <p class="text-xs text-graphite/60">Forcer cette norme pour cette entité et ses niveaux descendants</p>
                        </button>
                        <p class="px-4 pt-2 pb-1 text-xs text-graphite/50 border-t border-graphite/10 mt-1">
                            D'autres normes (Nigeria, Ghana, Afrique du Sud, États-Unis, Inde, Suisse, République tchèque, France, Rwanda...) apparaîtront ici dès que leurs documents comptables officiels seront fournis.
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-8">
            <div class="flex flex-wrap items-center justify-between gap-4 animate-[fadeInUp_0.55s_ease-out_both]">
                <input
                    type="month"
                    :value="month"
                    @change="changeMonth"
                    class="bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"
                />
                <Link
                    :href="`/org-units/${orgUnit.id}/finances/nouveau`"
                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20"
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
                    <p class="mt-2 text-2xl font-serif text-graphite">{{ formatAmount(totals.encaissements) }}</p>
                </div>
                <div class="glass-panel rounded-2xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-sanctuary-light">Décaissements</p>
                    <p class="mt-2 text-2xl font-serif text-graphite">{{ formatAmount(totals.decaissements) }}</p>
                </div>
                <div class="glass-panel rounded-2xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-graphite/68">Solde du mois</p>
                    <p class="mt-2 text-2xl font-serif text-graphite">{{ formatAmount(totals.solde) }}</p>
                </div>
            </div>

            <!--
                Corrige le 2026-09-12 (retour du ministere) : "Rapport
                d'activités du mois" retire d'ici - c'est un doublon, ce
                rapport a deja sa propre tuile independante sur le tableau
                de bord ("Rapport d'activités"), sans lien avec le module
                Finances. Seul reste ici ce qui concerne vraiment les
                finances.
            -->
            <div class="flex flex-wrap gap-3 text-sm animate-[fadeInUp_0.65s_ease-out_both]">
                <Link
                    :href="`/org-units/${orgUnit.id}/finances-rapport?mois=${month}`"
                    class="inline-flex items-center gap-1.5 glass-panel-light rounded-full px-4 py-2 font-medium text-graphite/87 hover:border-azure/40 hover:text-azure transition"
                >
                    Rapport financier du mois
                </Link>
            </div>

            <div v-if="transactions.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-graphite/5 text-graphite/62">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-graphite/80">Aucun mouvement enregistré ce mois-ci.</p>
                <p class="mt-1 text-sm text-graphite/62">Ajoute une dîme, une offrande ou une dépense pour commencer.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(transaction, i) in transactions"
                    :key="transaction.id"
                    class="flex items-center gap-4 glass-panel rounded-2xl p-5 hover:border-graphite/20 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
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
                            <p class="truncate font-semibold text-graphite text-[15px]">
                                {{ typeLabel(transaction.type) }}<span v-if="transaction.account_label"> · {{ transaction.account_label }}</span>
                            </p>
                            <p :class="['flex-shrink-0 font-semibold', transaction.nature === 'encaissement' ? 'text-forest' : 'text-sanctuary-light']">
                                {{ transaction.nature === 'encaissement' ? '+' : '-' }}{{ formatAmount(transaction.amount, transaction.currency) }}
                            </p>
                        </div>
                        <p class="mt-1 text-sm text-graphite/65">
                            {{ formatDate(transaction.transaction_date) }}
                            <span v-if="transaction.counterparty"> · {{ transaction.counterparty }}</span>
                            <span v-if="transaction.payment_method"> · {{ paymentMethodLabel(transaction.payment_method) }}</span>
                        </p>
                    </div>

                    <Link
                        :href="`/org-units/${orgUnit.id}/finances/${transaction.id}/modifier`"
                        class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-graphite/68 transition hover:bg-graphite/10 hover:text-graphite"
                    >
                        Modifier
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
