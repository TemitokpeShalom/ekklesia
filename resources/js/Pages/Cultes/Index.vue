<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
defineProps({
    orgUnit: Object,
    cultes: Array,
})

/**
 * Corrige le 2026-09-12 (retour du ministere : "une fois que c'est
 * enregistré, ça ne doit pas se présenter comme un culte planifié, ça doit
 * se présenter comme un culte enregistré") : le statut interne "planifie"
 * (date future, rien a archiver pour l'instant - voir CultesController)
 * reste inchangé en base, seul son intitulé affiché change.
 */
function statusLabel(status) {
    return { planifie: 'Enregistré', termine: 'Terminé', annule: 'Annulé' }[status] ?? status
}

function statusClass(status) {
    if (status === 'termine') return 'bg-forest/15 text-forest'
    if (status === 'annule') return 'bg-sanctuary/15 text-sanctuary-light'
    return 'bg-gold/15 text-sanctuary'
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
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Cultes
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">{{ cultes.length }} culte{{ cultes.length > 1 ? 's' : '' }}</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">Historique des cultes, messages prêchés et assistance.</p>
            </div>
        </template>

        <div class="space-y-8">
            <div class="flex justify-end animate-[fadeInUp_0.5s_ease-out_both]">
                <!-- Corrige le 2026-09-12 (retour du ministere) : "Ajouter un culte" -> "Enregistrer un culte", et couleur or -> bleu azur (comme les autres boutons de commande deja corriges module par module). -->
                <Link
                    :href="`/org-units/${orgUnit.id}/cultes/nouveau`"
                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Enregistrer un culte
                </Link>
            </div>

            <div v-if="cultes.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center animate-[fadeInUp_0.55s_ease-out_both]">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-graphite/5 text-graphite/62">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-graphite/80">Aucun culte enregistré pour le moment.</p>
                <p class="mt-1 text-sm text-graphite/62">Ajoute le premier culte pour suivre les messages et l'assistance.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(culte, i) in cultes"
                    :key="culte.id"
                    class="flex items-start gap-4 glass-panel rounded-2xl p-5 hover:border-graphite/20 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                    :style="{ animationDelay: `${Math.min(i, 10) * 40}ms` }"
                >
                    <div class="flex w-14 flex-shrink-0 flex-col items-center justify-center rounded-xl bg-graphite/5 border border-graphite/10 py-2">
                        <span class="text-lg font-bold leading-none text-graphite">{{ dayNumber(culte.service_date) }}</span>
                        <span class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-graphite/62">{{ monthAbbrev(culte.service_date) }}</span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate font-semibold text-graphite text-[15px]">{{ culte.title }}</p>
                            <span :class="['flex-shrink-0 rounded-full px-3 py-1 text-xs font-medium', statusClass(culte.status)]">
                                {{ statusLabel(culte.status) }}
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-graphite/65">
                            {{ formatDate(culte.service_date) }}
                            <span v-if="culte.start_time"> · {{ culte.start_time }}</span>
                            <span v-if="culte.speaker"> · {{ culte.speaker }}</span>
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-graphite/65">
                            <span v-if="culte.attendance_adults !== null || culte.attendance_children !== null" class="inline-flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-graphite/58">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                {{ culte.attendance_adults ?? 0 }} adultes, {{ culte.attendance_children ?? 0 }} enfants
                            </span>
                            <span v-if="culte.key_verses" class="inline-flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-graphite/58">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                                </svg>
                                {{ culte.key_verses }}
                            </span>
                        </div>
                    </div>

                    <Link
                        :href="`/org-units/${orgUnit.id}/cultes/${culte.id}/modifier`"
                        class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-graphite/68 transition hover:bg-graphite/10 hover:text-graphite"
                    >
                        Modifier
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
