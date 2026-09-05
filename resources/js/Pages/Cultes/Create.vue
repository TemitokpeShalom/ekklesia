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
    key_verses: '',
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
            <form @submit.prevent="submit" class="space-y-8 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <section>
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-semibold text-slate-700">Le message</h2>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Thème du message</label>
                            <input v-model="form.title" type="text" placeholder="La grâce de Dieu" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-rose-600">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Orateur</label>
                            <input v-model="form.speaker" type="text" placeholder="Pasteur..." class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                        </div>
                    </div>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-semibold text-slate-700">Quand</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Date</label>
                            <input v-model="form.service_date" type="date" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                            <p v-if="form.errors.service_date" class="mt-1 text-sm text-rose-600">{{ form.errors.service_date }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Heure</label>
                            <input v-model="form.start_time" type="time" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                        </div>
                    </div>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-semibold text-slate-700">Assistance</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Effectif adultes</label>
                            <input v-model="form.attendance_adults" type="number" min="0" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Effectif enfants</label>
                            <input v-model="form.attendance_children" type="number" min="0" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
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
                        <h2 class="text-sm font-semibold text-slate-700">Étude &amp; remarques</h2>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Versets clés étudiés</label>
                            <input v-model="form.key_verses" type="text" placeholder="Jean 3:16, Romains 8:28..." class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                            <p class="mt-1 text-xs text-slate-400">Sépare plusieurs références par une virgule.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                            <textarea v-model="form.notes" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100"></textarea>
                        </div>
                    </div>
                </section>

                <div class="border-t border-slate-100 pt-6">
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
                </div>
            </form>
        </main>
    </div>
</template>
