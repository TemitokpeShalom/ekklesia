<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Parcours de disciple (point 08), ecran construit directement dans le
 * style v3 "Vitrail". Chaque membre affiche ici son etape la plus
 * recente (calculee cote serveur depuis le journal complet) : l'historique
 * plus ancien reste en base meme s'il n'est pas encore consultable depuis
 * cet ecran.
 */
defineProps({
    orgUnit: Object,
    members: Array,
    stages: Object,
})

const badgeClasses = {
    nouveau_converti: 'bg-graphite/10 text-graphite/73',
    en_consolidation: 'bg-gold-soft/20 text-sanctuary',
    baptise: 'bg-sanctuary/15 text-sanctuary-light',
    membre: 'bg-azure/15 text-azure',
    en_formation: 'bg-gold/15 text-sanctuary',
    engage_service: 'bg-slateblue/15 text-slateblue',
    envoye_leader: 'bg-forest/15 text-forest',
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
                    Parcours de disciple
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">Croissance spirituelle des membres</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">L'étape la plus récente de chaque membre, du nouveau converti à l'envoi en service.</p>
            </div>
        </template>

        <div class="space-y-8">
            <div class="flex justify-end animate-[fadeInUp_0.5s_ease-out_both]">
                <Link
                    :href="`/org-units/${orgUnit.id}/parcours/nouveau`"
                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Enregistrer une étape
                </Link>
            </div>

            <div v-if="members.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center animate-[fadeInUp_0.55s_ease-out_both]">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-graphite/5 text-graphite/62">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-graphite/80">Aucun membre enregistré pour l'instant.</p>
                <p class="mt-1 text-sm text-graphite/62">Ajoute des membres avant de suivre leur parcours de disciple.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(member, i) in members"
                    :key="member.id"
                    class="flex items-center gap-4 glass-panel rounded-2xl p-5 hover:border-graphite/20 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                    :style="{ animationDelay: `${Math.min(i, 10) * 40}ms` }"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate font-semibold text-graphite text-[15px]">{{ member.first_name }} {{ member.last_name }}</p>
                            <span
                                v-if="member.latest_discipleship_stage"
                                :class="['flex-shrink-0 rounded-full px-3 py-1 text-xs font-medium', badgeClasses[member.latest_discipleship_stage.stage]]"
                            >
                                {{ stages[member.latest_discipleship_stage.stage] }}
                            </span>
                            <span v-else class="flex-shrink-0 rounded-full px-3 py-1 text-xs font-medium bg-graphite/5 text-graphite/58">
                                Aucune étape enregistrée
                            </span>
                        </div>
                        <p v-if="member.latest_discipleship_stage" class="mt-1 text-sm text-graphite/65">
                            Depuis le {{ formatDate(member.latest_discipleship_stage.reached_at) }}
                        </p>
                    </div>

                    <Link
                        v-if="member.latest_discipleship_stage"
                        :href="`/org-units/${orgUnit.id}/parcours/${member.latest_discipleship_stage.id}/modifier`"
                        class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-graphite/68 transition hover:bg-graphite/10 hover:text-graphite"
                    >
                        Modifier
                    </Link>
                    <Link
                        v-else
                        :href="`/org-units/${orgUnit.id}/parcours/nouveau?membre=${member.id}`"
                        class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-sanctuary transition hover:bg-graphite/10 hover:text-gold"
                    >
                        Enregistrer une étape
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
