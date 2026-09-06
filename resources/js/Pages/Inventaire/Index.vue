<script setup>
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    orgUnit: Object,
    assets: Array,
    categorie: String,
    totaux: Object,
})

function categoryLabel(category) {
    return category === 'immobilier' ? 'Immobilier' : 'Mobilier'
}

function provenanceLabel(value) {
    return {
        don: 'Don',
        achat_caisse: 'Achat sur caisse',
        achat_offrande: 'Achat sur offrande',
        subvention: 'Subvention',
        legs: 'Legs',
        construction: 'Construction',
    }[value] ?? value
}

function conditionLabel(value) {
    return {
        fonctionnel: 'Fonctionnel',
        a_surveiller: 'À surveiller',
        hors_service: 'Hors service',
    }[value] ?? value
}

function conditionClass(value) {
    return {
        fonctionnel: 'bg-emerald-50 text-emerald-600',
        a_surveiller: 'bg-amber-50 text-amber-600',
        hors_service: 'bg-rose-50 text-rose-600',
    }[value] ?? 'bg-slate-100 text-slate-500'
}

function formatAmount(value) {
    if (value === null || value === undefined) return '—'
    return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA'
}

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function filterBy(categorie) {
    router.get(`/org-units/${props.orgUnit.id}/inventaire`, categorie ? { categorie } : {})
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-5">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        {{ orgUnit.level_label }}
                    </p>
                    <h1 class="text-xl font-semibold text-slate-900">{{ orgUnit.name }}</h1>
                </div>
                <nav class="flex items-center gap-4 text-sm text-slate-500">
                    <Link :href="`/org-units/${orgUnit.id}`" class="hover:text-slate-900">Retour au tableau de bord</Link>
                    <Link href="/deconnexion" method="post" as="button" class="hover:text-slate-900">Se déconnecter</Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-6 py-8">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Inventaire des biens</h2>
                    <p class="mt-1 text-sm text-slate-500">Biens immobiliers et mobiliers, avec code d'identification automatique.</p>
                </div>
                <Link
                    :href="`/org-units/${orgUnit.id}/inventaire/nouveau`"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-700 hover:shadow"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Ajouter un bien
                </Link>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Valeur immobilier</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatAmount(totaux.immobilier) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Valeur mobilier</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatAmount(totaux.mobilier) }}</p>
                </div>
            </div>

            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex gap-2 text-sm">
                    <button
                        @click="filterBy(null)"
                        :class="['rounded-lg px-3 py-1.5 font-medium transition', !categorie ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50']"
                    >
                        Tous
                    </button>
                    <button
                        @click="filterBy('immobilier')"
                        :class="['rounded-lg px-3 py-1.5 font-medium transition', categorie === 'immobilier' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50']"
                    >
                        Immobilier
                    </button>
                    <button
                        @click="filterBy('mobilier')"
                        :class="['rounded-lg px-3 py-1.5 font-medium transition', categorie === 'mobilier' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50']"
                    >
                        Mobilier
                    </button>
                </div>
                <Link
                    :href="`/org-units/${orgUnit.id}/inventaire-rapport`"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Fiche d'inventaire consolidée
                </Link>
            </div>

            <div v-if="assets.length === 0" class="flex flex-col items-center rounded-2xl border border-dashed border-slate-300 bg-white px-8 py-14 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-slate-600">Aucun bien enregistré pour l'instant.</p>
                <p class="mt-1 text-sm text-slate-400">Ajoute un bâtiment, une parcelle ou du mobilier pour commencer.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="asset in assets"
                    :key="asset.id"
                    class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md"
                >
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 text-xs font-semibold">
                        {{ categoryLabel(asset.category).slice(0, 3).toUpperCase() }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate font-medium text-slate-900">{{ asset.label }}</p>
                            <p class="flex-shrink-0 font-semibold text-slate-900">{{ formatAmount(asset.acquisition_value) }}</p>
                        </div>
                        <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                            <span class="font-mono text-xs text-slate-400">{{ asset.code }}</span>
                            <span>· Qté {{ asset.quantity }}</span>
                            <span>· {{ provenanceLabel(asset.provenance) }}</span>
                            <span>· {{ formatDate(asset.acquisition_date) }}</span>
                            <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', conditionClass(asset.condition)]">
                                {{ conditionLabel(asset.condition) }}
                            </span>
                        </p>
                    </div>

                    <Link
                        :href="`/org-units/${orgUnit.id}/inventaire/${asset.id}/modifier`"
                        class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    >
                        Modifier
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
