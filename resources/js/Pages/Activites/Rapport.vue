<script setup>
import { useForm, Link, router } from '@inertiajs/vue3'

const props = defineProps({
    orgUnit: Object,
    month: String,
    report: Object,
    cultes: Array,
    effectifs: Object,
    canManage: Boolean,
})

const form = useForm({
    month: props.month,
    baptisms_count: props.report?.baptisms_count ?? '',
    new_converts_count: props.report?.new_converts_count ?? '',
    activities_notes: props.report?.activities_notes ?? '',
    remarks: props.report?.remarks ?? '',
    leader_notes: props.report?.leader_notes ?? '',
})

function monthLabel(value) {
    const [year, month] = value.split('-')
    return new Date(year, month - 1, 1).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
}

function changeMonth(event) {
    router.get(`/org-units/${props.orgUnit.id}/rapport-activites`, { mois: event.target.value })
}

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/rapport-activites`)
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
                    <h2 class="text-lg font-semibold text-slate-900">Rapport d'activités</h2>
                    <p class="mt-1 text-sm capitalize text-slate-500">{{ monthLabel(month) }}</p>
                </div>
                <input
                    type="month"
                    :value="month"
                    @change="changeMonth"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100"
                />
            </div>

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-slate-700">Effectifs (calculés depuis les cultes du mois)</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Adultes</p>
                        <p class="mt-1 text-xl font-semibold text-slate-900">{{ effectifs.adultes }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Enfants</p>
                        <p class="mt-1 text-xl font-semibold text-slate-900">{{ effectifs.enfants }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Cultes</p>
                        <p class="mt-1 text-xl font-semibold text-slate-900">{{ cultes.length }}</p>
                    </div>
                </div>
                <p class="mt-4 text-xs text-slate-400">Ces chiffres viennent directement du module Cultes, ils ne sont jamais ressaisis ici.</p>
            </section>

            <form @submit.prevent="submit" class="space-y-8 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <fieldset :disabled="!canManage" class="space-y-8">
                    <section>
                        <div class="mb-4 flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                </svg>
                            </span>
                            <h2 class="text-sm font-semibold text-slate-700">Baptêmes &amp; nouveaux convertis</h2>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Baptêmes</label>
                                <input v-model="form.baptisms_count" type="number" min="0" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Nouveaux convertis</label>
                                <input v-model="form.new_converts_count" type="number" min="0" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                            </div>
                        </div>
                    </section>

                    <section class="border-t border-slate-100 pt-6">
                        <div class="mb-4 flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </span>
                            <h2 class="text-sm font-semibold text-slate-700">Activités du mois</h2>
                        </div>
                        <textarea v-model="form.activities_notes" rows="3" placeholder="Évangélisations, réveils, événements particuliers..." class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100"></textarea>
                    </section>

                    <section class="border-t border-slate-100 pt-6">
                        <div class="mb-4 flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </span>
                            <h2 class="text-sm font-semibold text-slate-700">Remarques et suggestions</h2>
                        </div>
                        <textarea v-model="form.remarks" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100"></textarea>
                    </section>

                    <section class="border-t border-slate-100 pt-6">
                        <div class="mb-4 flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </span>
                            <h2 class="text-sm font-semibold text-slate-700">Situation du responsable</h2>
                        </div>
                        <textarea v-model="form.leader_notes" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100"></textarea>
                        <p class="mt-1 text-xs text-slate-400">Réservé à la hiérarchie pastorale directe, jamais visible dans une consolidation générale.</p>
                    </section>
                </fieldset>

                <div v-if="canManage" class="border-t border-slate-100 pt-6">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-700 hover:shadow disabled:opacity-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Enregistrer
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
