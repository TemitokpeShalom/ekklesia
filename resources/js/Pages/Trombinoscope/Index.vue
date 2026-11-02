<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

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
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`" back-label="Retour au tableau de bord">
        <template #actions>
            <button @click="print" class="inline-flex items-center gap-1.5 h-9 rounded-full bg-gradient-to-r from-azure to-azure-dark px-4 text-sm font-semibold text-white shadow-md shadow-azure/20 print:hidden">
                Imprimer
            </button>
        </template>

        <template #title>
            <p class="text-[11px] uppercase tracking-wide text-sanctuary/80 font-semibold">{{ orgUnit.level_label }}</p>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-graphite mt-1">Trombinoscope</h1>
            <p class="text-sm text-graphite/65 mt-1">{{ orgUnit.name }} · {{ members.length }} membre(s)</p>
        </template>

        <div v-if="members.length === 0" class="rounded-2xl bg-white border border-graphite/10 px-4 py-8 text-center text-sm text-graphite/55 print:hidden">
            Aucun membre actif avec fiche enregistrée pour l'instant.
        </div>

        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 print:grid-cols-4 print:gap-3">
            <div
                v-for="member in members"
                :key="member.id"
                class="flex flex-col items-center rounded-2xl border border-graphite/10 bg-white shadow-card p-4 text-center print:break-inside-avoid print:border-slate-300 print:shadow-none"
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
                <p class="mt-3 text-sm font-semibold text-graphite">{{ member.first_name }} {{ member.last_name }}</p>
                <p class="text-xs text-graphite/55">{{ member.org_unit?.name }}</p>
                <p v-if="member.phone" class="text-xs text-graphite/55">{{ member.phone }}</p>
            </div>
        </div>
    </AppLayout>
</template>
