<script setup>
import { Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    orgUnit: Object,
    date: String,
    parCategorie: Object,
})

const categories = [
    { key: 'immobilier', label: 'Biens immobiliers' },
    { key: 'mobilier', label: 'Biens mobiliers' },
]

function groupFor(key) {
    return props.parCategorie?.[key] ?? { items: [], total: 0 }
}

const totalGeneral = computed(() => {
    return categories.reduce((sum, c) => sum + (groupFor(c.key).total || 0), 0)
})

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

function formatAmount(value) {
    return new Intl.NumberFormat('fr-FR').format(value || 0) + ' FCFA'
}

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function changeDate(event) {
    router.get(`/org-units/${props.orgUnit.id}/inventaire-rapport`, { date: event.target.value })
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
                    <Link :href="`/org-units/${orgUnit.id}/inventaire`" class="hover:text-slate-900">Retour à l'inventaire</Link>
                    <Link href="/deconnexion" method="post" as="button" class="hover:text-slate-900">Se déconnecter</Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-6 py-8">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Fiche d'inventaire consolidée</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Biens propres de {{ orgUnit.name }} et de tous les niveaux qu'il regroupe, à la date choisie.
                    </p>
                </div>
                <input
                    type="date"
                    :value="date"
                    @change="changeDate"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100"
                />
            </div>

            <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Valeur totale du patrimoine</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatAmount(totalGeneral) }}</p>
            </div>

            <section v-for="cat in categories" :key="cat.key" class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">{{ cat.label }}</h3>
                    <p class="text-sm font-semibold text-slate-900">{{ formatAmount(groupFor(cat.key).total) }}</p>
                </div>
                <div v-if="groupFor(cat.key).items.length === 0" class="text-sm text-slate-400">Aucun bien dans cette catégorie.</div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="py-2 pr-3 font-medium">Code</th>
                                <th class="py-2 pr-3 font-medium">Bien</th>
                                <th class="py-2 pr-3 font-medium">Niveau</th>
                                <th class="py-2 pr-3 font-medium">Provenance</th>
                                <th class="py-2 pr-3 font-medium">Acquis le</th>
                                <th class="py-2 pr-3 font-medium">État</th>
                                <th class="py-2 text-right font-medium">Valeur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in groupFor(cat.key).items" :key="item.id" class="border-b border-slate-100 last:border-0">
                                <td class="py-2 pr-3 font-mono text-xs text-slate-500">{{ item.code }}</td>
                                <td class="py-2 pr-3 text-slate-700">{{ item.label }} <span class="text-slate-400">×{{ item.quantity }}</span></td>
                                <td class="py-2 pr-3 text-slate-500">{{ item.org_unit?.name }}</td>
                                <td class="py-2 pr-3 text-slate-500">{{ provenanceLabel(item.provenance) }}</td>
                                <td class="py-2 pr-3 text-slate-500">{{ formatDate(item.acquisition_date) }}</td>
                                <td class="py-2 pr-3 text-slate-500">{{ conditionLabel(item.condition) }}</td>
                                <td class="py-2 text-right font-medium text-slate-900">{{ formatAmount(item.acquisition_value) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <p class="text-xs text-slate-400">Cette fiche est calculée automatiquement depuis le registre des biens, jamais ressaisie séparément.</p>
        </main>
    </div>
</template>
