<script setup>
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    orgUnit: Object,
    month: String,
    encaissements: Array,
    decaissements: Array,
    totalEncaissements: [Number, String],
    totalDecaissements: [Number, String],
    solde: [Number, String],
})

function formatAmount(value) {
    return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA'
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
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-6 py-5">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        {{ orgUnit.level_label }}
                    </p>
                    <h1 class="text-xl font-semibold text-slate-900">{{ orgUnit.name }}</h1>
                </div>
                <nav class="flex items-center gap-4 text-sm text-slate-500">
                    <Link :href="`/org-units/${orgUnit.id}/finances`" class="hover:text-slate-900">Retour aux finances</Link>
                    <Link href="/deconnexion" method="post" as="button" class="hover:text-slate-900">Se déconnecter</Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-6 py-8">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Rapport financier</h2>
                    <p class="mt-1 text-sm capitalize text-slate-500">{{ monthLabel(month) }}</p>
                </div>
                <input
                    type="month"
                    :value="month"
                    @change="changeMonth"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100"
                />
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-emerald-600">Total encaissements</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatAmount(totalEncaissements) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-rose-600">Total décaissements</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatAmount(totalDecaissements) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Solde de trésorerie</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatAmount(solde) }}</p>
                </div>
            </div>

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-slate-700">Encaissements</h3>
                <div v-if="encaissements.length === 0" class="text-sm text-slate-400">Aucun encaissement ce mois-ci.</div>
                <table v-else class="w-full text-sm">
                    <tbody>
                        <tr v-for="line in encaissements" :key="line.account_code" class="border-b border-slate-100 last:border-0">
                            <td class="py-2 text-slate-500">{{ line.account_code }}</td>
                            <td class="py-2 text-slate-700">{{ line.account_label }}</td>
                            <td class="py-2 text-right font-medium text-slate-900">{{ formatAmount(line.total) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-slate-700">Décaissements</h3>
                <div v-if="decaissements.length === 0" class="text-sm text-slate-400">Aucun décaissement ce mois-ci.</div>
                <table v-else class="w-full text-sm">
                    <tbody>
                        <tr v-for="line in decaissements" :key="line.account_code" class="border-b border-slate-100 last:border-0">
                            <td class="py-2 text-slate-500">{{ line.account_code }}</td>
                            <td class="py-2 text-slate-700">{{ line.account_label }}</td>
                            <td class="py-2 text-right font-medium text-slate-900">{{ formatAmount(line.total) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <p class="text-xs text-slate-400">Ce rapport est calculé automatiquement depuis les mouvements enregistrés, jamais ressaisi séparément.</p>
        </main>
    </div>
</template>
