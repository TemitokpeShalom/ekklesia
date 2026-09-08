<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
})

const form = useForm({
    title: '',
    body: '',
    important: false,
    expires_at: '',
    attachment: null,
})

function onFileChange(event) {
    form.attachment = event.target.files[0] ?? null
}

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/annonces`)
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/annonces`" back-label="Annuler">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Annonces
                </p>
                <h1 class="font-serif text-3xl text-white mt-2">Nouvelle annonce</h1>
                <p class="text-sm text-white/55 mt-2 max-w-2xl">Publiée depuis {{ orgUnit.name }}, visible par {{ orgUnit.name }} et tout ce qui en dépend.</p>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <section>
                    <h2 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest mb-4">Contenu</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Titre</label>
                            <input v-model="form.title" type="text" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-rose-400">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Message (optionnel)</label>
                            <textarea v-model="form.body" rows="5" class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                            <p v-if="form.errors.body" class="mt-1 text-sm text-rose-400">{{ form.errors.body }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Pièce jointe (optionnel)</label>
                            <input type="file" @change="onFileChange" class="block w-full text-sm text-white/60 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white/80 hover:file:bg-white/20" />
                            <p class="mt-1 text-xs text-white/35">Image, PDF, document Word ou audio, 10 Mo maximum.</p>
                            <p v-if="form.errors.attachment" class="mt-1 text-sm text-rose-400">{{ form.errors.attachment }}</p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-white/10 pt-6">
                    <h2 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest mb-4">Options</h2>
                    <div class="space-y-4">
                        <label class="flex items-center gap-2 text-sm text-white/80">
                            <input v-model="form.important" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-white/5" />
                            Annonce importante (accusé de lecture demandé)
                        </label>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Expire le (optionnel)</label>
                            <input v-model="form.expires_at" type="date" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.expires_at" class="mt-1 text-sm text-rose-400">{{ form.errors.expires_at }}</p>
                        </div>
                    </div>
                </section>

                <div class="border-t border-white/10 pt-6">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Publier
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
