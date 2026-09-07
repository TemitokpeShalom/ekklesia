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
    <div class="min-h-screen bg-slate-50 print:bg-white">
        <header class="border-b border-slate-200 bg-white px-6 py-4 flex items-center justify-between print:hidden">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ orgUnit.level_label }}</p>
                <h1 class="text-lg font-semibold">{{ orgUnit.name }}</h1>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <Link :href="`/org-units/${orgUnit.id}`" class="text-slate-500 hover:text-slate-900">Retour au tableau de bord</Link>
                <button @click="print" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    Imprimer
                </button>
            </nav>
        </header>

        <main class="max-w-4xl mx-auto px-6 py-8 print:max-w-none print:px-0 print:py-0">
            <div class="mb-6 print:mb-4">
                <h2 class="text-lg font-semibold text-slate-900">Trombinoscope</h2>
                <p class="text-sm text-slate-500">{{ orgUnit.name }} · {{ members.length }} membre(s)</p>
            </div>

            <div v-if="members.length === 0" class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-400 print:hidden">
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
