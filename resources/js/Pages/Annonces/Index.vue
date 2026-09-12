<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
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
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-azure/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-azure/40"></span>
                    Annonces
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">Annonces</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">Diffusées depuis {{ orgUnit.name }} et tous les niveaux au-dessus.</p>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-8">
            <div v-if="canManage" class="flex justify-end animate-[fadeInUp_0.5s_ease-out_both]">
                <Link
                    :href="`/org-units/${orgUnit.id}/annonces/nouvelle`"
                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Publier une annonce
                </Link>
            </div>

            <div v-if="announcements.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center animate-[fadeInUp_0.55s_ease-out_both]">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-graphite/5 text-graphite/62">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73s-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-graphite/80">Aucune annonce pour l'instant.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(announcement, i) in announcements"
                    :key="announcement.id"
                    class="glass-panel rounded-2xl p-5 hover:border-graphite/20 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                    :class="announcement.important ? 'border-gold/40' : ''"
                    :style="{ animationDelay: `${Math.min(i, 10) * 40}ms` }"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span v-if="announcement.important" class="rounded-full bg-gold/15 px-2 py-0.5 text-xs font-medium text-sanctuary">Important</span>
                                <span v-if="!isRead(announcement)" class="rounded-full bg-sanctuary/15 px-2 py-0.5 text-xs font-medium text-sanctuary-light">Non lu</span>
                                <p class="font-semibold text-graphite text-[15px]">{{ announcement.title }}</p>
                            </div>
                            <p class="mt-1 text-xs text-graphite/62">
                                Publié depuis {{ announcement.org_unit?.name }} · {{ announcement.author?.name ?? 'Auteur inconnu' }} · {{ formatDate(announcement.created_at) }}
                                <span v-if="announcement.expires_at"> · expire le {{ formatDate(announcement.expires_at) }}</span>
                            </p>
                            <p v-if="announcement.body" class="mt-3 whitespace-pre-line text-sm text-graphite/80">{{ announcement.body }}</p>
                            <a
                                v-if="announcement.attachment_path"
                                :href="attachmentUrl(announcement)"
                                target="_blank"
                                class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-graphite/80 underline underline-offset-2 hover:text-sanctuary"
                            >
                                Pièce jointe : {{ announcement.attachment_original_name }}
                            </a>
                            <p v-if="canManage && announcement.important" class="mt-3 text-xs text-graphite/58">
                                {{ announcement.reads_count }} lecture(s) accusée(s).
                            </p>
                        </div>
                        <div class="flex flex-shrink-0 flex-col items-end gap-2">
                            <button
                                v-if="!isRead(announcement)"
                                @click="markRead(announcement)"
                                class="rounded-lg glass-panel-light px-3 py-1.5 text-xs font-medium text-graphite/80 transition hover:text-graphite"
                            >
                                Marquer comme lu
                            </button>
                            <Link
                                v-if="canManage && announcement.org_unit_id === orgUnit.id"
                                :href="`/org-units/${orgUnit.id}/annonces/${announcement.id}/modifier`"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium text-graphite/68 transition hover:bg-graphite/10 hover:text-graphite"
                            >
                                Modifier
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
