<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
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
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/annonces`" back-label="Retour">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-azure/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-azure/40"></span>
                    Annonces
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">Modifier l'annonce</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <section>
                    <h2 class="text-xs font-semibold text-azure uppercase tracking-widest mb-4">Contenu</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Titre</label>
                            <input v-model="form.title" type="text" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-rose-600">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Message (optionnel)</label>
                            <textarea v-model="form.body" rows="5" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                        </div>
                        <div v-if="announcement.attachment_path && !removeAttachment">
                            <p class="mb-1 text-sm font-medium text-graphite/87">Pièce jointe actuelle</p>
                            <div class="flex items-center justify-between rounded-xl border border-graphite/15 bg-graphite/5 px-3.5 py-2.5 text-sm">
                                <a :href="`/storage/${announcement.attachment_path}`" target="_blank" class="text-graphite/80 underline underline-offset-2 hover:text-sanctuary">
                                    {{ announcement.attachment_original_name }}
                                </a>
                                <button type="button" @click="toggleRemoveAttachment" class="text-xs font-medium text-rose-600 hover:underline">Retirer</button>
                            </div>
                        </div>
                        <div v-else-if="removeAttachment" class="rounded-xl border border-graphite/15 px-3.5 py-2.5 text-sm text-graphite/62">
                            La pièce jointe sera retirée à l'enregistrement.
                            <button type="button" @click="toggleRemoveAttachment" class="ml-2 text-xs font-medium text-graphite/80 hover:underline">Annuler</button>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">
                                {{ announcement.attachment_path ? 'Remplacer la pièce jointe (optionnel)' : 'Pièce jointe (optionnel)' }}
                            </label>
                            <input type="file" @change="onFileChange" class="block w-full text-sm text-graphite/73 file:mr-3 file:rounded-lg file:border-0 file:bg-graphite/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-graphite/87 hover:file:bg-graphite/20" />
                            <p class="mt-1 text-xs text-graphite/58">Image, PDF, document Word ou audio, 10 Mo maximum.</p>
                            <p v-if="form.errors.attachment" class="mt-1 text-sm text-rose-600">{{ form.errors.attachment }}</p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-xs font-semibold text-azure uppercase tracking-widest mb-4">Options</h2>
                    <div class="space-y-4">
                        <label class="flex items-center gap-2 text-sm text-graphite/87">
                            <input v-model="form.important" type="checkbox" class="h-4 w-4 rounded border-graphite/20 bg-graphite/5" />
                            Annonce importante (accusé de lecture demandé)
                        </label>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Expire le (optionnel)</label>
                            <input v-model="form.expires_at" type="date" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
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
