<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { queueAnnonce } from '@/offlineAnnoncesQueue'

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

const savedOffline = ref(false)
const offlineAttachmentError = ref(false)

// Hors connexion (2026-11-01) : l'annonce (texte seul) est gardee sur
// l'appareil puis publiee automatiquement au retour du reseau. Une piece
// jointe ne peut pas etre gardee de facon fiable : on le dit clairement au
// lieu de l'ignorer en silence.
function submit() {
    savedOffline.value = false
    offlineAttachmentError.value = false

    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        if (form.attachment) {
            offlineAttachmentError.value = true

            return
        }

        const { attachment, ...texte } = form.data()
        queueAnnonce(props.orgUnit.id, texte)
        form.reset()
        savedOffline.value = true

        return
    }

    form.post(`/org-units/${props.orgUnit.id}/annonces`)
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
                <h1 class="font-serif text-5xl font-bold text-graphite mt-2">Nouvelle annonce</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">Publiée depuis {{ orgUnit.name }}, visible par {{ orgUnit.name }} et tout ce qui en dépend.</p>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <div v-if="savedOffline" class="mb-4 rounded-2xl border border-gold/40 bg-gold/10 px-4 py-3 text-sm text-graphite/87 flex items-start gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 flex-shrink-0 text-gold">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <span>Annonce enregistrée sur cet appareil (pas de connexion pour le moment). Elle sera publiée automatiquement dès que la connexion revient, sans rien faire de plus.</span>
            </div>
            <p v-if="offlineAttachmentError" class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">Une pièce jointe ne peut pas être enregistrée sans connexion. Retirez le fichier pour publier le texte seul dès maintenant, ou attendez le retour du réseau pour tout envoyer ensemble.</p>
            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <section>
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Contenu</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Titre</label>
                            <input v-model="form.title" type="text" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-rose-600">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Message (optionnel)</label>
                            <textarea v-model="form.body" rows="5" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                            <p v-if="form.errors.body" class="mt-1 text-sm text-rose-600">{{ form.errors.body }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Pièce jointe (optionnel)</label>
                            <input type="file" @change="onFileChange" class="block w-full text-sm text-graphite/73 file:mr-3 file:rounded-lg file:border-0 file:bg-graphite/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-graphite/87 hover:file:bg-graphite/20" />
                            <p class="mt-1 text-xs text-graphite/58">Image, PDF, document Word ou audio, 10 Mo maximum.</p>
                            <p v-if="form.errors.attachment" class="mt-1 text-sm text-rose-600">{{ form.errors.attachment }}</p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Options</h2>
                    <div class="space-y-4">
                        <label class="flex items-center gap-2 text-sm text-graphite/87">
                            <input v-model="form.important" type="checkbox" class="h-4 w-4 rounded border-graphite/20 bg-graphite/5" />
                            Annonce importante (accusé de lecture demandé)
                        </label>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Expire le (optionnel)</label>
                            <input v-model="form.expires_at" type="date" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.expires_at" class="mt-1 text-sm text-rose-600">{{ form.errors.expires_at }}</p>
                        </div>
                    </div>
                </section>

                <div class="border-t border-graphite/10 pt-6">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20 disabled:opacity-60"
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
