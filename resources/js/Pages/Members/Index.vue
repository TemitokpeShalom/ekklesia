<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    orgUnit: Object,
    members: Array,
})
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white px-6 py-4 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ orgUnit.level_label }}</p>
                <h1 class="text-lg font-semibold">{{ orgUnit.name }}</h1>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <Link :href="`/org-units/${orgUnit.id}`" class="text-slate-500 hover:text-slate-900">Retour au tableau de bord</Link>
                <form method="post" action="/deconnexion">
                    <button class="text-sm text-slate-500 hover:text-slate-900">Se déconnecter</button>
                </form>
            </nav>
        </header>

        <main class="max-w-3xl mx-auto px-6 py-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">
                    Membres ({{ members.length }})
                </h2>
                <Link
                    :href="`/org-units/${orgUnit.id}/membres/nouveau`"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                >
                    Ajouter un membre
                </Link>
            </div>

            <div v-if="members.length === 0" class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-400">
                Aucun membre enregistré pour l'instant.
            </div>

            <ul v-else class="space-y-2">
                <li
                    v-for="member in members"
                    :key="member.id"
                    class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3"
                >
                    <div>
                        <p class="font-medium text-slate-900">{{ member.first_name }} {{ member.last_name }}</p>
                        <p class="text-xs text-slate-400">
                            <span v-if="member.phone">{{ member.phone }}</span>
                            <span v-if="member.phone && member.email"> · </span>
                            <span v-if="member.email">{{ member.email }}</span>
                            <span v-if="!member.phone && !member.email">Aucun contact renseigné</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="member.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                        >
                            {{ member.status === 'active' ? 'Actif' : 'Inactif' }}
                        </span>
                        <Link :href="`/org-units/${orgUnit.id}/membres/${member.id}/modifier`" class="text-slate-500 hover:text-slate-900">
                            Modifier
                        </Link>
                    </div>
                </li>
            </ul>
        </main>
    </div>
</template>
