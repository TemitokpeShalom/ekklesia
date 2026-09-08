<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
    transaction: Object,
    accounts: Object,
    accountingStandardLabel: String,
    currency: String,
})

const form = useForm({
    type: props.transaction.type,
    account_code: props.transaction.account_code,
    amount: props.transaction.amount,
    transaction_date: props.transaction.transaction_date,
    counterparty: props.transaction.counterparty,
    description: props.transaction.description,
})

const typeOptions = [
    { value: 'dime', label: 'Dîme' },
    { value: 'offrande', label: 'Offrande' },
    { value: 'action_de_grace', label: 'Action de grâce' },
    { value: 'don', label: 'Don' },
    { value: 'depense', label: 'Dépense' },
]

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
    form.put(`/org-units/${props.orgUnit.id}/finances/${props.transaction.id}`)
}

function destroy() {
    if (confirm('Retirer définitivement ce mouvement ?')) {
        router.delete(`/org-units/${props.orgUnit.id}/finances/${props.transaction.id}`)
    }
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/finances`" back-label="Annuler">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Finances
                </p>
                <h1 class="font-serif text-3xl text-white mt-2">Modifier le mouvement</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <section>
                    <h2 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest mb-4">Nature du mouvement</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Type</label>
                            <select v-model="form.type" @change="onTypeChange" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option v-for="option in typeOptions" :key="option.value" :value="option.value" class="bg-night text-white">{{ option.label }}</option>
                            </select>
                            <p v-if="form.errors.type" class="mt-1 text-sm text-rose-400">{{ form.errors.type }}</p>
                        </div>
                        <div v-if="accounts">
                            <label class="mb-1 block text-sm font-medium text-white/80">Compte comptable ({{ accountingStandardLabel }})</label>
                            <select v-model="form.account_code" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option value="" disabled class="bg-night text-white">Choisir un compte</option>
                                <option v-for="account in availableAccounts" :key="account.code" :value="account.code" class="bg-night text-white">
                                    {{ account.code }} · {{ account.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.account_code" class="mt-1 text-sm text-rose-400">{{ form.errors.account_code }}</p>
                        </div>
                        <p v-else class="text-sm text-gold-soft/90">
                            Aucune norme comptable n'est encore configurée pour ce pays : ce mouvement reste enregistré sans compte comptable (nature universelle seule).
                        </p>
                    </div>
                </section>

                <section class="border-t border-white/10 pt-6">
                    <h2 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest mb-4">Montant</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Montant ({{ currency }})</label>
                            <input v-model="form.amount" type="number" min="0" step="0.01" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.amount" class="mt-1 text-sm text-rose-400">{{ form.errors.amount }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Date</label>
                            <input v-model="form.transaction_date" type="date" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.transaction_date" class="mt-1 text-sm text-rose-400">{{ form.errors.transaction_date }}</p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-white/10 pt-6">
                    <h2 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest mb-4">Détails</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">De la part de / Payé à (optionnel)</label>
                            <input v-model="form.counterparty" type="text" class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Description</label>
                            <textarea v-model="form.description" rows="3" class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-between border-t border-white/10 pt-6">
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
                    <button
                        type="button"
                        @click="destroy"
                        class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-medium text-rose-400 transition hover:bg-rose-500/10"
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
