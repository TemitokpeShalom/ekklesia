<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { queueCulte } from '@/offlineCultesQueue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 *
 * Chantier "hors connexion", troisieme pierre (2026-09-19) : si l'appareil
 * n'a pas de reseau au moment d'enregistrer, le culte est garde sur
 * l'appareil (voir offlineCultesQueue.js) au lieu d'afficher une erreur,
 * puis envoye automatiquement des que la connexion revient (voir
 * OfflineIndicator.vue). Le formulaire reste sur place et se vide, pret
 * pour un eventuel culte suivant a saisir, plutot que de rediriger vers la
 * liste (redirection impossible a obtenir du serveur hors connexion).
 */
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

const savedOffline = ref(false)

function submit() {
    savedOffline.value = false

    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        queueCulte(props.orgUnit.id, { ...form.data() })
        form.reset()
        savedOffline.value = true

        return
    }

    form.post(`/org-units/${props.orgUnit.id}/cultes`)
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/cultes`" back-label="Retour">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Cultes
                </p>
                <h1 class="font-serif text-5xl font-bold text-graphite mt-2">Nouveau culte</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <div
                v-if="savedOffline"
                class="mb-4 rounded-2xl border border-gold/40 bg-gold/10 px-4 py-3 text-sm text-graphite/87 flex items-start gap-2"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 flex-shrink-0 text-gold">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <span>Culte enregistré sur cet appareil (pas de connexion pour le moment). Il sera envoyé automatiquement vers la plateforme dès que la connexion revient, sans rien faire de plus.</span>
            </div>

            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <section>
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Le message</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Thème du message</label>
                            <input v-model="form.title" type="text" placeholder="La grâce de Dieu" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-rose-600">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Orateur</label>
                            <input v-model="form.speaker" type="text" placeholder="Pasteur..." class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Quand</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Date</label>
                            <input v-model="form.service_date" type="date" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.service_date" class="mt-1 text-sm text-rose-600">{{ form.errors.service_date }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Heure</label>
                            <input v-model="form.start_time" type="time" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Assistance</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Effectif adultes</label>
                            <input v-model="form.attendance_adults" type="number" min="0" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Effectif enfants</label>
                            <input v-model="form.attendance_children" type="number" min="0" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Étude et remarques</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Versets clés étudiés</label>
                            <input v-model="form.key_verses" type="text" placeholder="Jean 3:16, Romains 8:28..." class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p class="mt-1 text-xs text-graphite/58">Sépare plusieurs références par une virgule.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Notes (résumé du message)</label>
                            <textarea v-model="form.notes" rows="3" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
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
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
