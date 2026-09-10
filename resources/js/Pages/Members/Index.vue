<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
defineProps({
    orgUnit: Object,
    members: Array,
})

function initials(member) {
    return `${member.first_name?.[0] ?? ''}${member.last_name?.[0] ?? ''}`.toUpperCase()
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Membres
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">{{ members.length }} membre{{ members.length > 1 ? 's' : '' }}</h1>
            </div>
        </template>

        <div class="space-y-8">
            <div class="flex justify-end animate-[fadeInUp_0.5s_ease-out_both]">
                <Link
                    :href="`/org-units/${orgUnit.id}/membres/nouveau`"
                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Ajouter un membre
                </Link>
            </div>

            <div v-if="members.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center animate-[fadeInUp_0.55s_ease-out_both]">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-graphite/5 text-graphite/62">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-graphite/80">Aucun membre enregistré pour l'instant.</p>
                <p class="mt-1 text-sm text-graphite/62">Ajoute un premier membre pour commencer.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(member, i) in members"
                    :key="member.id"
                    class="flex items-center gap-4 glass-panel rounded-2xl p-5 hover:border-graphite/20 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                    :style="{ animationDelay: `${Math.min(i, 10) * 40}ms` }"
                >
                    <img
                        v-if="member.photo_path"
                        :src="`/storage/${member.photo_path}`"
                        class="h-10 w-10 flex-shrink-0 rounded-full object-cover border border-graphite/15"
                    />
                    <span
                        v-else
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-graphite/5 text-xs font-semibold text-graphite/73"
                    >
                        {{ initials(member) }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate font-semibold text-graphite text-[15px]">{{ member.first_name }} {{ member.last_name }}</p>
                            <span
                                class="flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="member.status === 'active' ? 'bg-forest/15 text-forest' : 'bg-graphite/10 text-graphite/68'"
                            >
                                {{ member.status === 'active' ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-graphite/65">
                            <span v-if="member.phone">{{ member.phone }}</span>
                            <span v-if="member.phone && member.email"> · </span>
                            <span v-if="member.email">{{ member.email }}</span>
                            <span v-if="!member.phone && !member.email">Aucun contact renseigné</span>
                        </p>
                    </div>

                    <Link
                        :href="`/org-units/${orgUnit.id}/membres/${member.id}/modifier`"
                        class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-graphite/68 transition hover:bg-graphite/10 hover:text-graphite"
                    >
                        Modifier
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
