<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
    accounts: Object,
    accountingStandardLabel: String,
    currency: String,
    paymentMethods: Array,
})

const form = useForm({
    type: 'dime',
    account_code: '',
    amount: '',
    payment_method: '',
    transaction_date: '',
    counterparty: '',
    description: '',
})

const typeOptions = [
    { value: 'dime', label: 'Dîme' },
    { value: 'offrande', label: 'Offrande' },
    { value: 'action_de_grace', label: 'Action de grâce' },
    { value: 'don', label: 'Don' },
    { value: 'depense', label: 'Dépense' },
]

const paymentMethodLabels = {
    especes: 'Espèces',
    cheque: 'Chèque',
    virement: 'Virement bancaire',
    mobile_money: 'Mobile Money',
    autre: 'Autre',
}

const availableAccounts = computed(() => {
    if (!props.accounts) {
        return []
    }
    if (form.type === 'depense') {
        return props.accounts.expense
    }
    return props.accounts.income[form.type] ?? []
})

function onTypeChange() {
    form.account_code = ''
}

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/finances`)
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/finances`" back-label="Annuler">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Finances
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">Nouveau mouvement</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <section>
                    <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest mb-4">Nature du mouvement</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Type</label>
                            <select v-model="form.type" @change="onTypeChange" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option v-for="option in typeOptions" :key="option.value" :value="option.value" class="bg-white text-graphite">{{ option.label }}</option>
                            </select>
                            <p v-if="form.errors.type" class="mt-1 text-sm text-rose-600">{{ form.errors.type }}</p>
                        </div>
                        <div v-if="accounts">
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Compte comptable ({{ accountingStandardLabel }})</label>
                            <select v-model="form.account_code" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option value="" disabled class="bg-white text-graphite">Choisir un compte</option>
                                <option v-for="account in availableAccounts" :key="account.code" :value="account.code" class="bg-white text-graphite">
                                    {{ account.code }} · {{ account.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.account_code" class="mt-1 text-sm text-rose-600">{{ form.errors.account_code }}</p>
                        </div>
                        <p v-else class="text-sm text-sanctuary/90">
                            Aucune norme comptable n'est encore configurée pour ce pays : ce mouvement sera enregistré sans compte comptable (nature universelle seule).
                        </p>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest mb-4">Montant</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Montant ({{ currency }})</label>
                            <input v-model="form.amount" type="number" min="0" step="0.01" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.amount" class="mt-1 text-sm text-rose-600">{{ form.errors.amount }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Date</label>
                            <input v-model="form.transaction_date" type="date" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.transaction_date" class="mt-1 text-sm text-rose-600">{{ form.errors.transaction_date }}</p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest mb-4">Mode de règlement (optionnel)</h2>
                    <select v-model="form.payment_method" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                        <option value="" class="bg-white text-graphite">Non précisé</option>
                        <option v-for="method in paymentMethods" :key="method" :value="method" class="bg-white text-graphite">{{ paymentMethodLabels[method] ?? method }}</option>
                    </select>
                    <p v-if="form.errors.payment_method" class="mt-1 text-sm text-rose-600">{{ form.errors.payment_method }}</p>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest mb-4">Détails</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">De la part de / Payé à (optionnel)</label>
                            <input v-model="form.counterparty" type="text" placeholder="Nom du fidèle, fournisseur..." class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Description</label>
                            <textarea v-model="form.description" rows="3" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                        </div>
                    </div>
                </section>

                <div class="border-t border-graphite/10 pt-6">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
