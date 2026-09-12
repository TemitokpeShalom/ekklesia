<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    orgUnit: Object,
    members: Array,
})

function initials(member) {
    return `${member.first_name?.[0] ?? ''}${member.last_name?.[0] ?? ''}`.toUpperCase()
}

function print() {
    window.print()
}
</script>

<template>
    <div class="min-h-screen bg-night print:bg-white">
        <!--
            v3 "Vitrail" (2026-09-09) : seule cette barre de navigation (masquee
            a l'impression) reprend l'habillage sombre commun. La grille
            imprimable ci-dessous reste volontairement en clair : c'est un
            gabarit destine au papier, pas un ecran d'application.
        -->
        <header class="border-b border-white/10 glass-panel px-6 py-4 flex items-center justify-between print:hidden">
            <div>
                <p class="text-xs uppercase tracking-wide text-azure/80">{{ orgUnit.level_label }}</p>
                <h1 class="text-4xl font-bold text-white">{{ orgUnit.name }}</h1>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <Link :href="`/org-units/${orgUnit.id}`" class="text-white/60 hover:text-white">Retour au tableau de bord</Link>
                <button @click="print" class="rounded-lg bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 px-4 py-2 text-sm font-semibold text-white">
                    Imprimer
                </button>
            </nav>
        </header>

        <main class="max-w-4xl mx-auto px-6 py-8 print:max-w-none print:px-0 print:py-0">
            <div class="mb-6 print:mb-4">
                <h2 class="text-4xl font-bold text-white print:text-slate-900">Trombinoscope</h2>
                <p class="text-sm text-white/50 print:text-slate-500">{{ orgUnit.name }} · {{ members.length }} membre(s)</p>
            </div>

            <div v-if="members.length === 0" class="glass-panel rounded-2xl px-4 py-8 text-center text-sm text-white/40 print:hidden">
                Aucun membre actif avec fiche enregistrée pour l'instant.
            </div>

            <div v-else class="grid grid-cols-3 gap-4 print:grid-cols-4 print:gap-3">
                <div
                    v-for="member in members"
                    :key="member.id"
                    class="flex flex-col items-center rounded-lg border border-slate-200 bg-white p-4 text-center print:break-inside-avoid print:border-slate-300"
                >
                    <img
                        v-if="member.photo_path"
                        :src="`/storage/${member.photo_path}`"
                        class="h-20 w-20 rounded-full object-cover border border-slate-200"
                    />
                    <span
                        v-else
                        class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-lg font-medium text-slate-500"
                    >
                        {{ initials(member) }}
                    </span>
                    <p class="mt-3 text-sm font-medium text-slate-900">{{ member.first_name }} {{ member.last_name }}</p>
                    <p class="text-xs text-slate-400">{{ member.org_unit?.name }}</p>
                    <p v-if="member.phone" class="text-xs text-slate-400">{{ member.phone }}</p>
                </div>
            </div>
        </main>
    </div>
</template>
