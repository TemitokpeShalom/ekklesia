<script setup>
import { useForm, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    orgUnit: Object,
    transaction: Object,
    accounts: Object,
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
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-2xl items-center justify-between px-6 py-5">
                <h1 class="text-xl font-semibold text-slate-900">Modifier le mouvement</h1>
                <Link :href="`/org-units/${orgUnit.id}/finances`" class="text-sm text-slate-500 hover:text-slate-900">Annuler</Link>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-6 py-8">
            <form @submit.prevent="submit" class="space-y-8 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <section>
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-semibold text-slate-700">Nature du mouvement</h2>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Type</label>
                            <select v-model="form.type" @change="onTypeChange" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                <option v-for="option in typeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                            <p v-if="form.errors.type" class="mt-1 text-sm text-rose-600">{{ form.errors.type }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Compte comptable (SYSCOHADA)</label>
                            <select v-model="form.account_code" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                <option value="" disabled>Choisir un compte</option>
                                <option v-for="account in availableAccounts" :key="account.code" :value="account.code">
                                    {{ account.code }} · {{ account.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.account_code" class="mt-1 text-sm text-rose-600">{{ form.errors.account_code }}</p>
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
                        <h2 class="text-sm font-semibold text-slate-700">Montant</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Montant (FCFA)</label>
                            <input v-model="form.amount" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                            <p v-if="form.errors.amount" class="mt-1 text-sm text-rose-600">{{ form.errors.amount }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Date</label>
                            <input v-model="form.transaction_date" type="date" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                            <p v-if="form.errors.transaction_date" class="mt-1 text-sm text-rose-600">{{ form.errors.transaction_date }}</p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-semibold text-slate-700">Détails</h2>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">De la part de / Payé à (optionnel)</label>
                            <input v-model="form.counterparty" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                            <textarea v-model="form.description" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100"></textarea>
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
