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
    if (status === 'termine') return 'bg-emerald-50 text-emerald-700'
    if (status === 'annule') return 'bg-rose-50 text-rose-700'
    return 'bg-amber-50 text-amber-700'
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
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Cultes ({{ cultes.length }})</h2>
                <Link
                    :href="`/org-units/${orgUnit.id}/cultes/nouveau`"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                >
                    Ajouter un culte
                </Link>
            </div>

            <div v-if="cultes.length === 0" class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                Aucun culte enregistré pour le moment.
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="culte in cultes"
                    :key="culte.id"
                    class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4"
                >
                    <div>
                        <p class="font-medium text-slate-900">{{ culte.title }}</p>
                        <p class="text-sm text-slate-500">
                            {{ formatDate(culte.service_date) }}
                            <span v-if="culte.start_time"> à {{ culte.start_time }}</span>
                            <span v-if="culte.speaker"> · {{ culte.speaker }}</span>
                            <span v-if="culte.attendance_count !== null"> · {{ culte.attendance_count }} présents</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span :class="['rounded-full px-3 py-1 text-xs font-medium', statusClass(culte.status)]">
                            {{ statusLabel(culte.status) }}
                        </span>
                        <Link :href="`/org-units/${orgUnit.id}/cultes/${culte.id}/modifier`" class="text-sm text-slate-500 hover:text-slate-900">
                            Modifier
                        </Link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
