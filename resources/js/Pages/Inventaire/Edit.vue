<script setup>
import { useForm, Link, router } from '@inertiajs/vue3'

const props = defineProps({
    orgUnit: Object,
    asset: Object,
    depenses: Array,
})

const form = useForm({
    category: props.asset.category,
    label: props.asset.label,
    quantity: props.asset.quantity,
    acquisition_date: props.asset.acquisition_date,
    acquisition_value: props.asset.acquisition_value,
    provenance: props.asset.provenance,
    financial_transaction_id: props.asset.financial_transaction_id ?? '',
    condition: props.asset.condition,
    observation: props.asset.observation,
})

const provenanceOptions = [
    { value: 'don', label: 'Don' },
    { value: 'achat_caisse', label: 'Achat sur caisse' },
    { value: 'achat_offrande', label: 'Achat sur offrande' },
    { value: 'subvention', label: 'Subvention' },
    { value: 'legs', label: 'Legs' },
    { value: 'construction', label: 'Construction' },
]

const conditionOptions = [
    { value: 'fonctionnel', label: 'Fonctionnel' },
    { value: 'a_surveiller', label: 'Fonctionnel mais à surveiller' },
    { value: 'hors_service', label: 'Hors service' },
]

function formatDepense(depense) {
    const montant = new Intl.NumberFormat('fr-FR').format(depense.amount)
    const date = new Date(depense.transaction_date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
    return `${date} · ${depense.account_label} · ${montant} ${depense.currency}`
}

function submit() {
    form.put(`/org-units/${props.orgUnit.id}/inventaire/${props.asset.id}`)
}

function destroy() {
    if (confirm('Retirer définitivement ce bien du registre ?')) {
        router.delete(`/org-units/${props.orgUnit.id}/inventaire/${props.asset.id}`)
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-2xl items-center justify-between px-6 py-5">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Modifier le bien</h1>
                    <p class="mt-1 font-mono text-xs text-slate-400">{{ asset.code }}</p>
                </div>
                <Link :href="`/org-units/${orgUnit.id}/inventaire`" class="text-sm text-slate-500 hover:text-slate-900">Annuler</Link>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-6 py-8">
            <form @submit.prevent="submit" class="space-y-8 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <section>
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-semibold text-slate-700">Identification</h2>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Catégorie</label>
                            <select v-model="form.category" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                <option value="immobilier">Immobilier (parcelle, bâtiment)</option>
                                <option value="mobilier">Mobilier (matériel, meubles)</option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1 text-sm text-rose-600">{{ form.errors.category }}</p>
                            <p class="mt-1 text-xs text-slate-400">Changer la catégorie ne modifie pas le code déjà attribué.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Désignation du bien</label>
                            <input v-model="form.label" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                            <p v-if="form.errors.label" class="mt-1 text-sm text-rose-600">{{ form.errors.label }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Quantité</label>
                            <input v-model="form.quantity" type="number" min="1" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                            <p v-if="form.errors.quantity" class="mt-1 text-sm text-rose-600">{{ form.errors.quantity }}</p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.625c.621 0 1.125.504 1.125 1.125v.375M3.75 4.5h16.5M2.25 6.75h19.5M2.25 6.75v-.375c0-.621.504-1.125 1.125-1.125h17.25c.621 0 1.125.504 1.125 1.125v.375" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-semibold text-slate-700">Acquisition</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Date d'acquisition</label>
                            <input v-model="form.acquisition_date" type="date" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                            <p v-if="form.errors.acquisition_date" class="mt-1 text-sm text-rose-600">{{ form.errors.acquisition_date }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Valeur (FCFA)</label>
                            <input v-model="form.acquisition_value" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                            <p v-if="form.errors.acquisition_value" class="mt-1 text-sm text-rose-600">{{ form.errors.acquisition_value }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Provenance</label>
                        <select v-model="form.provenance" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100">
                            <option v-for="option in provenanceOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <p v-if="form.errors.provenance" class="mt-1 text-sm text-rose-600">{{ form.errors.provenance }}</p>
                    </div>
                    <div v-if="depenses.length > 0" class="mt-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Lier à une dépense déjà enregistrée (optionnel)</label>
                        <select v-model="form.financial_transaction_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100">
                            <option value="">Aucune</option>
                            <option v-for="depense in depenses" :key="depense.id" :value="depense.id">{{ formatDepense(depense) }}</option>
                        </select>
                    </div>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-semibold text-slate-700">État et observation</h2>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">État du bien</label>
                            <select v-model="form.condition" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                <option v-for="option in conditionOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                            <p v-if="form.errors.condition" class="mt-1 text-sm text-rose-600">{{ form.errors.condition }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Observation (optionnel)</label>
                            <textarea v-model="form.observation" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100"></textarea>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-between border-t border-slate-100 pt-6">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-700 hover:shadow disabled:opacity-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Enregistrer
                    </button>
                    <button
                        type="button"
                        @click="destroy"
                        class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        Retirer
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
