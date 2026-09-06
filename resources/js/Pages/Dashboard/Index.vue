<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    orgUnit: Object,
    children: Array,
    activeAffectations: Array,
});
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white px-6 py-4 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ orgUnit.level_label }}</p>
                <h1 class="text-lg font-semibold">{{ orgUnit.name }}</h1>
            </div>
            <form method="post" action="/deconnexion">
                <button class="text-sm text-slate-500 hover:text-slate-900">Se déconnecter</button>
            </form>
        </header>

        <main class="max-w-3xl mx-auto px-6 py-8">
            <section class="mb-8">
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">
                    Modules
                </h2>
                <ul class="space-y-2">
                    <li>
                        <a :href="`/org-units/${orgUnit.id}/membres`"
                            class="block bg-white border border-slate-200 rounded px-4 py-3 text-sm hover:border-slate-400">
                            <span class="text-xs uppercase tracking-wide text-slate-400 block">Module</span>
                            Membres
                        </a>
                    </li>
                    <li>
                        <a :href="`/org-units/${orgUnit.id}/cultes`"
                            class="block bg-white border border-slate-200 rounded px-4 py-3 text-sm hover:border-slate-400">
                            <span class="text-xs uppercase tracking-wide text-slate-400 block">Module</span>
                            Cultes
                        </a>
                    </li>
                    <li>
                        <a :href="`/org-units/${orgUnit.id}/finances`"
                            class="block bg-white border border-slate-200 rounded px-4 py-3 text-sm hover:border-slate-400">
                            <span class="text-xs uppercase tracking-wide text-slate-400 block">Module</span>
                            Finances
                        </a>
                    </li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">
                    Mes affectations actives
                </h2>
                <ul class="space-y-2">
                    <li v-for="a in activeAffectations" :key="a.id"
                        class="bg-white border border-slate-200 rounded px-4 py-2 text-sm">
                        <span class="font-medium">{{ a.role.label }}</span>
                        - {{ a.org_unit.name }}
                    </li>
                </ul>
            </section>

            <section>
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">
                    {{ children.length ? 'Entités directement rattachées' : 'Aucune entité rattachée pour l'instant' }}
                </h2>
                <ul class="space-y-2">
                    <li v-for="child in children" :key="child.id">
                        <a :href="`/org-units/${child.id}`"
                            class="block bg-white border border-slate-200 rounded px-4 py-3 text-sm hover:border-slate-400">
                            <span class="text-xs uppercase tracking-wide text-slate-400 block">{{ child.level_label }}</span>
                            {{ child.name }}
                        </a>
                    </li>
                </ul>

                <p class="text-sm text-slate-400 mt-6">
                    Ce tableau de bord est volontairement vide, les prochains modules y ajouteront leurs indicateurs.
                </p>
            </section>
        </main>
    </div>
</template>
