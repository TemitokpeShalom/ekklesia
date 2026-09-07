<script setup>
import { useForm, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    orgUnit: Object,
    announcement: Object,
})

const removeAttachment = ref(false)

const form = useForm({
    title: props.announcement.title,
    body: props.announcement.body,
    important: props.announcement.important,
    expires_at: props.announcement.expires_at ? props.announcement.expires_at.substring(0, 10) : '',
    attachment: null,
    remove_attachment: false,
})

function onFileChange(event) {
    form.attachment = event.target.files[0] ?? null
}

function toggleRemoveAttachment() {
    removeAttachment.value = !removeAttachment.value
    form.remove_attachment = removeAttachment.value
}

function submit() {
    form.put(`/org-units/${props.orgUnit.id}/annonces/${props.announcement.id}`)
}

function destroy() {
    if (confirm('Retirer définitivement cette annonce ?')) {
        router.delete(`/org-units/${props.orgUnit.id}/annonces/${props.announcement.id}`)
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-2xl items-center justify-between px-6 py-5">
                <h1 class="text-xl font-semibold text-slate-900">Modifier l'annonce</h1>
                <Link :href="`/org-units/${orgUnit.id}/annonces`" class="text-sm text-slate-500 hover:text-slate-900">Annuler</Link>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-6 py-8">
            <form @submit.prevent="submit" class="space-y-8 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <section>
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-semibold text-slate-700">Contenu</h2>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Titre</label>
                            <input v-model="form.title" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-rose-600">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Message (optionnel)</label>
                            <textarea v-model="form.body" rows="5" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100"></textarea>
                        </div>
                        <div v-if="announcement.attachment_path && !removeAttachment">
                            <p class="mb-1 text-sm font-medium text-slate-700">Pièce jointe actuelle</p>
                            <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <a :href="`/storage/${announcement.attachment_path}`" target="_blank" class="text-slate-600 underline underline-offset-2 hover:text-slate-900">
                                    {{ announcement.attachment_original_name }}
                                </a>
                                <button type="button" @click="toggleRemoveAttachment" class="text-xs font-medium text-rose-600 hover:underline">Retirer</button>
                            </div>
                        </div>
                        <div v-else-if="removeAttachment" class="rounded-lg border border-dashed border-slate-300 px-3 py-2 text-sm text-slate-400">
                            La pièce jointe sera retirée à l'enregistrement.
                            <button type="button" @click="toggleRemoveAttachment" class="ml-2 text-xs font-medium text-slate-600 hover:underline">Annuler</button>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                {{ announcement.attachment_path ? 'Remplacer la pièce jointe (optionnel)' : 'Pièce jointe (optionnel)' }}
                            </label>
                            <input type="file" @change="onFileChange" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200" />
                            <p class="mt-1 text-xs text-slate-400">Image, PDF, document Word ou audio, 10 Mo maximum.</p>
                            <p v-if="form.errors.attachment" class="mt-1 text-sm text-rose-600">{{ form.errors.attachment }}</p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-[18px] w-[18px]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-semibold text-slate-700">Options</h2>
                    </div>
                    <div class="space-y-4">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input v-model="form.important" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                            Annonce importante (accusé de lecture demandé)
                        </label>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Expire le (optionnel)</label>
                            <input v-model="form.expires_at" type="date" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm transition focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-100" />
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
