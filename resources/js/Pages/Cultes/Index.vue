<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    orgUnit: Object,
    cultes: Array,
})

function statusLabel(status) {
    return { planifie: 'Planifié', termine: 'Terminé', annule: 'Annulé' }[status] ?? status
}

function statusClass(status) {
    if (status === 'termine') return 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200'
    if (status === 'annule') return 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200'
    return 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200'
}

function dayNumber(value) {
    return new Date(value).getDate()
}

function monthAbbrev(value) {
    return new Date(value).toLocaleDateString('fr-FR', { month: 'short' }).replace('.', '').toUpperCase()
}

function formatDate(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
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
            <div class="mb-6 flex items-end justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Cultes ({{ cultes.length }})</h2>
                    <p class="mt-1 text-sm text-slate-500">Historique des cultes, messages prêchés et assistance.</p>
                </div>
                <Link
                    :href="`/org-units/${orgUnit.id}/cultes/nouveau`"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-700 hover:shadow"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Ajouter un culte
                </Link>
            </div>

            <div v-if="cultes.length === 0" class="flex flex-col items-center rounded-2xl border border-dashed border-slate-300 bg-white px-8 py-14 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-slate-600">Aucun culte enregistré pour le moment.</p>
                <p class="mt-1 text-sm text-slate-400">Ajoute le premier culte pour suivre les messages et l'assistance.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="culte in cultes"
                    :key="culte.id"
                    class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md"
                >
                    <div class="flex w-14 flex-shrink-0 flex-col items-center justify-center rounded-xl border border-slate-200 bg-slate-50 py-2">
                        <span class="text-lg font-bold leading-none text-slate-900">{{ dayNumber(culte.service_date) }}</span>
                        <span class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ monthAbbrev(culte.service_date) }}</span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate font-medium text-slate-900">{{ culte.title }}</p>
                            <span :class="['flex-shrink-0 rounded-full px-3 py-1 text-xs font-medium', statusClass(culte.status)]">
                                {{ statusLabel(culte.status) }}
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ formatDate(culte.service_date) }}
                            <span v-if="culte.start_time"> · {{ culte.start_time }}</span>
                            <span v-if="culte.speaker"> · {{ culte.speaker }}</span>
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                            <span v-if="culte.attendance_adults !== null || culte.attendance_children !== null" class="inline-flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-slate-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                {{ culte.attendance_adults ?? 0 }} adultes, {{ culte.attendance_children ?? 0 }} enfants
                            </span>
                            <span v-if="culte.key_verses" class="inline-flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-slate-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                                </svg>
                                {{ culte.key_verses }}
                            </span>
                        </div>
                    </div>

                    <Link
                        :href="`/org-units/${orgUnit.id}/cultes/${culte.id}/modifier`"
                        class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    >
                        Modifier
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
