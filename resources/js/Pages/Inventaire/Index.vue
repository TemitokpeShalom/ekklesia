<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
    assets: Array,
    categorie: String,
    totaux: Object,
    currency: String,
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
        fonctionnel: 'bg-forest/15 text-forest',
        a_surveiller: 'bg-gold/15 text-sanctuary',
        hors_service: 'bg-sanctuary/15 text-sanctuary-light',
    }[value] ?? 'bg-graphite/10 text-graphite/68'
}

function formatAmount(value) {
    if (value === null || value === undefined) return '-'
    return new Intl.NumberFormat('fr-FR').format(value) + ' ' + props.currency
}

function formatDate(value) {
    if (!value) return '-'
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function filterBy(categorie) {
    router.get(`/org-units/${props.orgUnit.id}/inventaire`, categorie ? { categorie } : {})
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-azure/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-azure/40"></span>
                    Inventaire des biens
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">Immobilier et mobilier</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">Avec code d'identification automatique, jamais réattribué même après retrait.</p>
            </div>
        </template>

        <div class="space-y-8">
            <div class="flex justify-end animate-[fadeInUp_0.5s_ease-out_both]">
                <Link
                    :href="`/org-units/${orgUnit.id}/inventaire/nouveau`"
                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Ajouter un bien
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 animate-[fadeInUp_0.55s_ease-out_both]">
                <div class="glass-panel rounded-2xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-graphite/68">Valeur immobilier</p>
                    <p class="mt-2 text-2xl font-serif text-graphite">{{ formatAmount(totaux.immobilier) }}</p>
                </div>
                <div class="glass-panel rounded-2xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-graphite/68">Valeur mobilier</p>
                    <p class="mt-2 text-2xl font-serif text-graphite">{{ formatAmount(totaux.mobilier) }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 animate-[fadeInUp_0.6s_ease-out_both]">
                <div class="flex gap-2 text-sm">
                    <button
                        @click="filterBy(null)"
                        :class="['rounded-full px-4 py-1.5 font-medium transition', !categorie ? 'bg-gradient-to-r from-azure to-azure-dark text-white' : 'glass-panel-light text-graphite/80 hover:text-graphite']"
                    >
                        Tous
                    </button>
                    <button
                        @click="filterBy('immobilier')"
                        :class="['rounded-full px-4 py-1.5 font-medium transition', categorie === 'immobilier' ? 'bg-gradient-to-r from-azure to-azure-dark text-white' : 'glass-panel-light text-graphite/80 hover:text-graphite']"
                    >
                        Immobilier
                    </button>
                    <button
                        @click="filterBy('mobilier')"
                        :class="['rounded-full px-4 py-1.5 font-medium transition', categorie === 'mobilier' ? 'bg-gradient-to-r from-azure to-azure-dark text-white' : 'glass-panel-light text-graphite/80 hover:text-graphite']"
                    >
                        Mobilier
                    </button>
                </div>
                <Link
                    :href="`/org-units/${orgUnit.id}/inventaire-rapport`"
                    class="inline-flex items-center gap-1.5 glass-panel-light rounded-full px-4 py-2 text-sm font-medium text-graphite/87 hover:border-azure/40 hover:text-azure transition"
                >
                    Fiche d'inventaire consolidée
                </Link>
            </div>

            <div v-if="assets.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-graphite/5 text-graphite/62">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-graphite/80">Aucun bien enregistré pour l'instant.</p>
                <p class="mt-1 text-sm text-graphite/62">Ajoute un bâtiment, une parcelle ou du mobilier pour commencer.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(asset, i) in assets"
                    :key="asset.id"
                    class="flex items-center gap-4 glass-panel rounded-2xl p-5 hover:border-graphite/20 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                    :style="{ animationDelay: `${Math.min(i, 10) * 40}ms` }"
                >
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-graphite/5 text-graphite/73 text-xs font-semibold">
                        {{ categoryLabel(asset.category).slice(0, 3).toUpperCase() }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate font-semibold text-graphite text-[15px]">{{ asset.label }}</p>
                            <p class="flex-shrink-0 font-semibold text-graphite">{{ formatAmount(asset.acquisition_value) }}</p>
                        </div>
                        <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-graphite/65">
                            <span class="font-mono text-xs text-graphite/58">{{ asset.code }}</span>
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
                        class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-graphite/68 transition hover:bg-graphite/10 hover:text-graphite"
                    >
                        Modifier
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
