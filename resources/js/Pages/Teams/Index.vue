<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Equipes et benevolat (point 08), ecran construit directement dans le
 * style v3 "Vitrail".
 */
defineProps({
    orgUnit: Object,
    teams: Array,
})
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Équipes et bénévolat
                </p>
                <h1 class="font-serif text-3xl text-white mt-2">{{ teams.length }} équipe{{ teams.length > 1 ? 's' : '' }}</h1>
                <p class="text-sm text-white/55 mt-2 max-w-2xl">Accueil, louange, enfants, technique... constitue les équipes de service et affecte les membres.</p>
            </div>
        </template>

        <div class="space-y-8">
            <div class="flex justify-end animate-[fadeInUp_0.5s_ease-out_both]">
                <Link
                    :href="`/org-units/${orgUnit.id}/equipes/nouvelle`"
                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Créer une équipe
                </Link>
            </div>

            <div v-if="teams.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center animate-[fadeInUp_0.55s_ease-out_both]">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/5 text-white/40">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-white/70">Aucune équipe créée pour le moment.</p>
                <p class="mt-1 text-sm text-white/40">Crée une première équipe pour organiser le service.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(team, i) in teams"
                    :key="team.id"
                    class="flex items-center gap-4 glass-panel rounded-2xl p-5 hover:border-white/20 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                    :style="{ animationDelay: `${Math.min(i, 10) * 40}ms` }"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate font-semibold text-white text-[15px]">{{ team.name }}</p>
                            <span class="flex-shrink-0 rounded-full px-3 py-1 text-xs font-medium bg-slateblue/15 text-slateblue">
                                {{ team.team_members_count }} membre{{ team.team_members_count > 1 ? 's' : '' }}
                            </span>
                        </div>
                        <p v-if="team.description" class="mt-1 text-sm text-white/45">{{ team.description }}</p>
                    </div>

                    <Link
                        :href="`/org-units/${orgUnit.id}/equipes/${team.id}/modifier`"
                        class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-white/50 transition hover:bg-white/10 hover:text-white"
                    >
                        Modifier
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
