<script setup>
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    orgUnit: Object,
    transactions: Array,
    month: String,
    totals: Object,
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

function formatAmount(value) {
    return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA'
}

function formatDate(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function changeMonth(event) {
    router.get(`/org-units/${props.orgUnit.id}/finances`, { mois: event.target.value })
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-5">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        {{ orgUnit.level_label }}
                    </p>
                    <h1 class="text-xl font-semibold text-slate-900">{{ orgUnit.name }}</h1>
                </div>
                <nav class="flex items-center gap-4 text-sm text-slate-500">
                    <Link :href="`/org-units/${orgUnit.id}`" class="hover:text-slate-900">Retour au tableau de bord</Link>
                    <Link href="/deconnexion" method="post" as="button" class="hover:text-slate-900">Se déconnecter</Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-6 py-8">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Finances</h2>
                    <p class="mt-1 text-sm text-slate-500">Dîmes, offrandes, actions de grâce, dons et dépenses.</p>
                </div>
                <div class="flex items-center gap-3">
                    <input
                        type="month"
                        :value="month"
                        @change="changeMonth"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100"
                    />
                    <Link
                        :href="`/org-units/${orgUnit.id}/finances/nouveau`"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-700 hover:shadow"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Ajouter un mouvement
                    </Link>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-emerald-600">Encaissements</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatAmount(totals.encaissements) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-rose-600">Décaissements</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatAmount(totals.decaissements) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Solde du mois</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatAmount(totals.solde) }}</p>
                </div>
            </div>

            <div class="mb-6 flex flex-wrap gap-3 text-sm">
                <Link
                    :href="`/org-units/${orgUnit.id}/finances-rapport?mois=${month}`"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Rapport financier du mois
                </Link>
                <Link
                    :href="`/org-units/${orgUnit.id}/rapport-activites?mois=${month}`"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Rapport d'activités du mois
                </Link>
            </div>

            <div v-if="transactions.length === 0" class="flex flex-col items-center rounded-2xl border border-dashed border-slate-300 bg-white px-8 py-14 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-slate-600">Aucun mouvement enregistré ce mois-ci.</p>
                <p class="mt-1 text-sm text-slate-400">Ajoute une dîme, une offrande ou une dépense pour commencer.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="transaction in transactions"
                    :key="transaction.id"
                    class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md"
                >
                    <span
                        :class="[
                            'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl',
                            transaction.nature === 'encaissement' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600',
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
                            <p class="truncate font-medium text-slate-900">{{ typeLabel(transaction.type) }} · {{ transaction.account_label }}</p>
                            <p :class="['flex-shrink-0 font-semibold', transaction.nature === 'encaissement' ? 'text-emerald-600' : 'text-rose-600']">
                                {{ transaction.nature === 'encaissement' ? '+' : '-' }}{{ formatAmount(transaction.amount) }}
                            </p>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ formatDate(transaction.transaction_date) }}
                            <span v-if="transaction.counterparty"> · {{ transaction.counterparty }}</span>
                        </p>
                    </div>

                    <Link
                        :href="`/org-units/${orgUnit.id}/finances/${transaction.id}/modifier`"
                        class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    >
                        Modifier
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
