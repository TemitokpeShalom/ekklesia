<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Sacrements individuels (point 08), ecran construit directement dans le
 * style v3 "Vitrail" : bapteme et mariage de chaque membre, hors du
 * registre des cultes (qui, lui, reste un evenement collectif).
 */
defineProps({
    orgUnit: Object,
    sacraments: Array,
})

function typeLabel(type) {
    return { bapteme: 'Baptême', mariage: 'Mariage' }[type] ?? type
}

function typeBadgeClass(type) {
    return type === 'mariage' ? 'bg-sanctuary/15 text-sanctuary-light' : 'bg-forest/15 text-forest'
}

/**
 * Corrige le 2026-09-12 (retour du ministere) : la personne concernée
 * n'est pas toujours un membre enregistré (voir Sacraments/Create.vue,
 * member_name) - sans repli, ces sacrements affichaient à tort "Membre
 * retiré", alors que la personne n'a jamais été supprimée puisqu'elle
 * n'a jamais été enregistrée. On distingue donc bien les deux cas.
 */
function memberName(member, fallbackName) {
    if (member) return `${member.first_name} ${member.last_name}`
    if (fallbackName) return fallbackName
    return 'Membre retiré'
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
                    Sacrements
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">{{ sacraments.length }} sacrement{{ sacraments.length > 1 ? 's' : '' }}</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">Baptêmes et mariages enregistrés individuellement, pour chaque membre.</p>
            </div>
        </template>

        <div class="space-y-8">
            <div class="flex justify-end animate-[fadeInUp_0.5s_ease-out_both]">
                <Link
                    :href="`/org-units/${orgUnit.id}/sacrements/nouveau`"
                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Enregistrer un sacrement
                </Link>
            </div>

            <div v-if="sacraments.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center animate-[fadeInUp_0.55s_ease-out_both]">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-graphite/5 text-graphite/62">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0-13.5v6l3.75 2.25" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-graphite/80">Aucun sacrement enregistré pour le moment.</p>
                <p class="mt-1 text-sm text-graphite/62">Enregistre le premier baptême ou mariage pour garder trace de la vie des membres.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(s, i) in sacraments"
                    :key="s.id"
                    class="flex items-start gap-4 glass-panel rounded-2xl p-5 hover:border-graphite/20 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                    :style="{ animationDelay: `${Math.min(i, 10) * 40}ms` }"
                >
                    <div class="flex w-14 flex-shrink-0 flex-col items-center justify-center rounded-xl bg-graphite/5 border border-graphite/10 py-2">
                        <span class="text-lg font-bold leading-none text-graphite">{{ dayNumber(s.event_date) }}</span>
                        <span class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-graphite/62">{{ monthAbbrev(s.event_date) }}</span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate font-semibold text-graphite text-[15px]">
                                {{ memberName(s.member, s.member_name) }}
                                <span v-if="s.type === 'mariage'" class="text-graphite/65 font-normal">
                                    &amp; {{ s.spouse_member ? memberName(s.spouse_member) : (s.spouse_name || 'conjoint(e) non enregistré(e)') }}
                                </span>
                            </p>
                            <span :class="['flex-shrink-0 rounded-full px-3 py-1 text-xs font-medium', typeBadgeClass(s.type)]">
                                {{ typeLabel(s.type) }}
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-graphite/65">
                            {{ formatDate(s.event_date) }}
                            <span v-if="s.officiant"> · {{ s.officiant }}</span>
                            <span v-if="s.location"> · {{ s.location }}</span>
                        </p>

                        <p v-if="s.notes" class="mt-2 text-sm text-graphite/73">{{ s.notes }}</p>
                    </div>

                    <Link
                        :href="`/org-units/${orgUnit.id}/sacrements/${s.id}/modifier`"
                        class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-graphite/68 transition hover:bg-graphite/10 hover:text-graphite"
                    >
                        Modifier
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
