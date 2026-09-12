<script setup>
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
    asset: Object,
    depenses: Array,
    currency: String,
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
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/inventaire`" back-label="Retour">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-azure/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-azure/40"></span>
                    Inventaire des biens
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">Modifier le bien</h1>
                <p class="mt-1 font-mono text-xs text-graphite/58">{{ asset.code }}</p>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <section>
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Identification</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Catégorie</label>
                            <select v-model="form.category" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option value="immobilier" class="bg-white text-graphite">Immobilier (parcelle, bâtiment)</option>
                                <option value="mobilier" class="bg-white text-graphite">Mobilier (matériel, meubles)</option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1 text-sm text-rose-600">{{ form.errors.category }}</p>
                            <p class="mt-1 text-xs text-graphite/58">Changer la catégorie ne modifie pas le code déjà attribué.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Désignation du bien</label>
                            <input v-model="form.label" type="text" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.label" class="mt-1 text-sm text-rose-600">{{ form.errors.label }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Quantité</label>
                            <input v-model="form.quantity" type="number" min="1" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.quantity" class="mt-1 text-sm text-rose-600">{{ form.errors.quantity }}</p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Acquisition</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Date d'acquisition</label>
                            <input v-model="form.acquisition_date" type="date" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.acquisition_date" class="mt-1 text-sm text-rose-600">{{ form.errors.acquisition_date }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Valeur ({{ currency }})</label>
                            <input v-model="form.acquisition_value" type="number" min="0" step="0.01" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.acquisition_value" class="mt-1 text-sm text-rose-600">{{ form.errors.acquisition_value }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="mb-1 block text-sm font-medium text-graphite/87">Provenance</label>
                        <select v-model="form.provenance" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                            <option v-for="option in provenanceOptions" :key="option.value" :value="option.value" class="bg-white text-graphite">{{ option.label }}</option>
                        </select>
                        <p v-if="form.errors.provenance" class="mt-1 text-sm text-rose-600">{{ form.errors.provenance }}</p>
                    </div>
                    <div v-if="depenses.length > 0" class="mt-4">
                        <label class="mb-1 block text-sm font-medium text-graphite/87">Lier à une dépense déjà enregistrée (optionnel)</label>
                        <select v-model="form.financial_transaction_id" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                            <option value="" class="bg-white text-graphite">Aucune</option>
                            <option v-for="depense in depenses" :key="depense.id" :value="depense.id" class="bg-white text-graphite">{{ formatDepense(depense) }}</option>
                        </select>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">État et observation</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">État du bien</label>
                            <select v-model="form.condition" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option v-for="option in conditionOptions" :key="option.value" :value="option.value" class="bg-white text-graphite">{{ option.label }}</option>
                            </select>
                            <p v-if="form.errors.condition" class="mt-1 text-sm text-rose-600">{{ form.errors.condition }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Observation (optionnel)</label>
                            <textarea v-model="form.observation" rows="3" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-between border-t border-graphite/10 pt-6">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20 disabled:opacity-60"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Enregistrer
                    </button>
                    <button
                        type="button"
                        @click="destroy"
                        class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-medium text-rose-600 transition hover:bg-rose-500/10"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        Retirer
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
