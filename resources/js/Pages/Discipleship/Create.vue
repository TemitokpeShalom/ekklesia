<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { queueParcours } from '@/offlineDiscipleshipQueue'

/**
 * Parcours de disciple (point 08), ecran construit directement dans le
 * style v3 "Vitrail". Le membre peut arriver deja selectionne (lien depuis
 * la liste, ?membre=...) pour eviter une recherche inutile dans la liste
 * deroulante.
 *
 * Chantier "hors connexion", sixieme pierre (2026-09-19) : meme principe
 * que Cultes/Create.vue et Sacraments/Create.vue - aucun fichier dans ce
 * formulaire, donc rien a desactiver hors connexion (voir
 * offlineDiscipleshipQueue.js).
 */
const props = defineProps({
    orgUnit: Object,
    members: Array,
    stages: Object,
    preselectedMemberId: String,
})

const form = useForm({
    member_id: props.preselectedMemberId || '',
    new_member_name: '',
    stage: 'nouveau_converti',
    reached_at: '',
    notes: '',
})

const savedOffline = ref(false)

function memberLabel(member) {
    return `${member.first_name} ${member.last_name}`
}

function submit() {
    savedOffline.value = false

    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        queueParcours(props.orgUnit.id, { ...form.data() })
        form.reset()
        savedOffline.value = true

        return
    }

    form.post(`/org-units/${props.orgUnit.id}/parcours`)
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/parcours`" back-label="Retour">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Parcours de disciple
                </p>
                <h1 class="font-serif text-5xl font-bold text-graphite mt-2">Enregistrer une étape</h1>
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
                <span>Étape enregistrée sur cet appareil (pas de connexion pour le moment). Elle sera envoyée automatiquement vers la plateforme dès que la connexion revient, sans rien faire de plus.</span>
            </div>

            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <section>
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Membre concerné</h2>
                    <!-- Corrige le 2026-09-12 (retour du ministere) : la personne concernée n'est pas toujours déjà enregistrée sur la plateforme - même mécanisme que Sacrements (membre OU nom). Ici, un nom saisi crée une fiche membre minimale, complétable ensuite depuis le module Membres, pour que la personne reste suivie partout (et pas seulement ici). -->
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Si déjà membre enregistré</label>
                            <select v-model="form.member_id" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option value="" class="bg-white text-graphite">Aucun</option>
                                <option v-for="m in members" :key="m.id" :value="m.id" class="bg-white text-graphite">{{ memberLabel(m) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Sinon, nom complet</label>
                            <input v-model="form.new_member_name" type="text" placeholder="Non enregistré(e) sur cette plateforme" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p class="mt-1 text-xs text-graphite/55">Une fiche membre simplifiée sera créée automatiquement avec ce nom.</p>
                        </div>
                    </div>
                    <p v-if="form.errors.member_id" class="mt-1 text-sm text-rose-600">{{ form.errors.member_id }}</p>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Étape franchie</h2>
                    <select v-model="form.stage" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                        <option v-for="(label, key) in stages" :key="key" :value="key" class="bg-white text-graphite">{{ label }}</option>
                    </select>
                    <p v-if="form.errors.stage" class="mt-1 text-sm text-rose-600">{{ form.errors.stage }}</p>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Date</h2>
                    <input v-model="form.reached_at" type="date" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.reached_at" class="mt-1 text-sm text-rose-600">{{ form.errors.reached_at }}</p>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Notes</h2>
                    <textarea v-model="form.notes" rows="3" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
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
