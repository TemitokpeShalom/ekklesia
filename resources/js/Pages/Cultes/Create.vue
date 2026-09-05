<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    orgUnit: Object,
})

const form = useForm({
    title: '',
    service_date: '',
    start_time: '',
    speaker: '',
    attendance_adults: '',
    attendance_children: '',
    notes: '',
})

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/cultes`)
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-2xl items-center justify-between px-6 py-5">
                <h1 class="text-xl font-semibold text-slate-900">Nouveau culte</h1>
                <Link :href="`/org-units/${orgUnit.id}/cultes`" class="text-sm text-slate-500 hover:text-slate-900">Annuler</Link>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-6 py-8">
            <form @submit.prevent="submit" class="space-y-5 rounded-lg border border-slate-200 bg-white p-6">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Thème du message</label>
                    <input v-model="form.title" type="text" placeholder="La grâce de Dieu" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                    <p v-if="form.errors.title" class="mt-1 text-sm text-rose-600">{{ form.errors.title }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Date</label>
                        <input v-model="form.service_date" type="date" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                        <p v-if="form.errors.service_date" class="mt-1 text-sm text-rose-600">{{ form.errors.service_date }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Heure</label>
                        <input v-model="form.start_time" type="time" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Orateur</label>
                    <input v-model="form.speaker" type="text" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Effectif adultes</label>
                        <input v-model="form.attendance_adults" type="number" min="0" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Effectif enfants</label>
                        <input v-model="form.attendance_children" type="number" min="0" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                    <textarea v-model="form.notes" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></textarea>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50"
                >
                    Enregistrer
                </button>
            </form>
        </main>
    </div>
</template>
