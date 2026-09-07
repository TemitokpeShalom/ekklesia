<script setup>
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    orgUnit: Object,
    announcements: Array,
    readAnnouncementIds: Array,
    canManage: Boolean,
})

function isRead(announcement) {
    return props.readAnnouncementIds.includes(announcement.id)
}

function markRead(announcement) {
    router.post(`/org-units/${props.orgUnit.id}/annonces/${announcement.id}/lu`, {}, { preserveScroll: true })
}

function formatDate(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function attachmentUrl(announcement) {
    return `/storage/${announcement.attachment_path}`
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
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Annonces</h2>
                    <p class="mt-1 text-sm text-slate-500">Diffusées depuis {{ orgUnit.name }} et tous les niveaux au-dessus.</p>
                </div>
                <Link
                    v-if="canManage"
                    :href="`/org-units/${orgUnit.id}/annonces/nouvelle`"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-700 hover:shadow"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Publier une annonce
                </Link>
            </div>

            <div v-if="announcements.length === 0" class="flex flex-col items-center rounded-2xl border border-dashed border-slate-300 bg-white px-8 py-14 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73s-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-slate-600">Aucune annonce pour l'instant.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="announcement in announcements"
                    :key="announcement.id"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md"
                    :class="announcement.important ? 'border-amber-300' : ''"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span v-if="announcement.important" class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Important</span>
                                <span v-if="!isRead(announcement)" class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">Non lu</span>
                                <p class="font-medium text-slate-900">{{ announcement.title }}</p>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">
                                Publié depuis {{ announcement.org_unit?.name }} · {{ announcement.author?.name ?? 'Auteur inconnu' }} · {{ formatDate(announcement.created_at) }}
                                <span v-if="announcement.expires_at"> · expire le {{ formatDate(announcement.expires_at) }}</span>
                            </p>
                            <p v-if="announcement.body" class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ announcement.body }}</p>
                            <a
                                v-if="announcement.attachment_path"
                                :href="attachmentUrl(announcement)"
                                target="_blank"
                                class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 underline underline-offset-2 hover:text-slate-900"
                            >
                                Pièce jointe : {{ announcement.attachment_original_name }}
                            </a>
                            <p v-if="canManage && announcement.important" class="mt-3 text-xs text-slate-400">
                                {{ announcement.reads_count }} lecture(s) accusée(s).
                            </p>
                        </div>
                        <div class="flex flex-shrink-0 flex-col items-end gap-2">
                            <button
                                v-if="!isRead(announcement)"
                                @click="markRead(announcement)"
                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                            >
                                Marquer comme lu
                            </button>
                            <Link
                                v-if="canManage && announcement.org_unit_id === orgUnit.id"
                                :href="`/org-units/${orgUnit.id}/annonces/${announcement.id}/modifier`"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                            >
                                Modifier
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
