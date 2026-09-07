<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    orgUnit: Object,
    messages: Array,
    search: String,
})

const query = ref(props.search)

function submitSearch() {
    router.get(`/org-units/${props.orgUnit.id}/bibliotheque`, { q: query.value }, { preserveState: true })
}

function formatDate(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
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
                    <Link :href="`/org-units/${orgUnit.id}`" class="hover:text-slate-900">Retour au tableau de bord</Link>
                    <Link href="/deconnexion" method="post" as="button" class="hover:text-slate-900">Se déconnecter</Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-6 py-8">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">Bibliothèque ministérielle</h2>
                <p class="mt-1 text-sm text-slate-500">Messages prêchés dans tout le ministère, accès réservé à ceux qui prêchent.</p>
            </div>

            <form @submit.prevent="submitSearch" class="mb-6 flex gap-2">
                <input
                    v-model="query"
                    type="text"
                    placeholder="Rechercher un thème, un orateur, un verset..."
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100"
                />
                <button
                    type="submit"
                    class="flex-shrink-0 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700"
                >
                    Rechercher
                </button>
            </form>

            <div v-if="messages.length === 0" class="flex flex-col items-center rounded-2xl border border-dashed border-slate-300 bg-white px-8 py-14 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-slate-600">Aucun message trouvé.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-medium text-slate-900">{{ message.title }}</p>
                        <span class="flex-shrink-0 text-xs text-slate-400">{{ formatDate(message.service_date) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">
                        {{ message.org_unit?.name }}
                        <span v-if="message.speaker"> · {{ message.speaker }}</span>
                    </p>
                    <p v-if="message.key_verses" class="mt-3 text-sm text-slate-600">
                        <span class="font-medium text-slate-700">Versets :</span> {{ message.key_verses }}
                    </p>
                    <p v-if="message.notes" class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ message.notes }}</p>
                </div>
            </div>
        </main>
    </div>
</template>
