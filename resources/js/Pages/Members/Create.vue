<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { queueMembre } from '@/offlineMembresQueue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 *
 * Chantier "hors connexion", quatrieme pierre (2026-09-19) : meme principe
 * que Cultes/Create.vue (voir ce fichier et offlineMembresQueue.js pour le
 * raisonnement) - hors connexion, le membre est garde sur l'appareil au
 * lieu d'afficher une erreur. Difference avec les cultes : les deux champs
 * photo sont desactives hors connexion (voir offlineMembresQueue.js pour
 * pourquoi), avec une note expliquant qu'elles pourront etre ajoutees plus
 * tard depuis la fiche du membre.
 */
const props = defineProps({
    orgUnit: Object,
    honorificTitles: Array,
})

const form = useForm({
    first_name: '',
    last_name: '',
    title: '',
    phone: '',
    email: '',
    gender: '',
    birth_date: '',
    joined_at: '',
    photo: null,
    spouse_name: '',
    spouse_photo: null,
})

const savedOffline = ref(false)
const isOffline = ref(typeof navigator !== 'undefined' ? !navigator.onLine : false)

function updateOfflineState() {
    isOffline.value = typeof navigator !== 'undefined' ? !navigator.onLine : false
}

onMounted(() => {
    window.addEventListener('online', updateOfflineState)
    window.addEventListener('offline', updateOfflineState)
})

onUnmounted(() => {
    window.removeEventListener('online', updateOfflineState)
    window.removeEventListener('offline', updateOfflineState)
})

function onPhotoChange(event) {
    form.photo = event.target.files[0] ?? null
}

function onSpousePhotoChange(event) {
    form.spouse_photo = event.target.files[0] ?? null
}

function submit() {
    savedOffline.value = false

    if (isOffline.value) {
        const { photo, spouse_photo, ...donneesSansPhoto } = form.data()
        queueMembre(props.orgUnit.id, donneesSansPhoto)
        form.reset()
        savedOffline.value = true

        return
    }

    form.post(`/org-units/${props.orgUnit.id}/membres`)
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/membres`" back-label="Retour">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-azure/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-azure/40"></span>
                    Membres
                </p>
                <h1 class="font-serif text-5xl font-bold text-graphite mt-2">Ajouter un membre</h1>
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
                <span>Membre enregistré sur cet appareil (pas de connexion pour le moment). Il sera envoyé automatiquement vers la plateforme dès que la connexion revient, sans rien faire de plus.</span>
            </div>

            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]" enctype="multipart/form-data">
                <section>
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Identité</h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-graphite/87">Prénom</label>
                                <input v-model="form.first_name" type="text" required class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                                <p v-if="form.errors.first_name" class="mt-1 text-sm text-rose-600">{{ form.errors.first_name }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-graphite/87">Nom</label>
                                <input v-model="form.last_name" type="text" required class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                                <p v-if="form.errors.last_name" class="mt-1 text-sm text-rose-600">{{ form.errors.last_name }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Titre honorifique</label>
                            <select v-model="form.title" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option value="" class="bg-white text-graphite">Aucun</option>
                                <option v-for="t in honorificTitles" :key="t" :value="t" class="bg-white text-graphite">{{ t }}</option>
                            </select>
                            <p v-if="form.errors.title" class="mt-1 text-sm text-rose-600">{{ form.errors.title }}</p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Contact</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Téléphone</label>
                            <input v-model="form.phone" type="text" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Email</label>
                            <input v-model="form.email" type="email" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Détails</h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-graphite/87">Genre</label>
                                <select v-model="form.gender" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                    <option value="" class="bg-white text-graphite">Non précisé</option>
                                    <option value="M" class="bg-white text-graphite">Homme</option>
                                    <option value="F" class="bg-white text-graphite">Femme</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-graphite/87">Date de naissance</label>
                                <input v-model="form.birth_date" type="date" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Date d'adhésion</label>
                            <input v-model="form.joined_at" type="date" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Photos et conjoint(e)</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Photo (optionnel)</label>
                            <input type="file" accept="image/*" :disabled="isOffline" @change="onPhotoChange" class="block w-full text-sm text-graphite/73 file:mr-3 file:rounded-lg file:border-0 file:bg-graphite/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-graphite/87 hover:file:bg-graphite/20 disabled:opacity-50" />
                            <p class="mt-1 text-xs text-graphite/58" v-if="!isOffline">Image, 5 Mo maximum.</p>
                            <p class="mt-1 text-xs text-gold" v-else>Pas disponible hors connexion, tu pourras l'ajouter plus tard en modifiant la fiche.</p>
                            <p v-if="form.errors.photo" class="mt-1 text-sm text-rose-600">{{ form.errors.photo }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Conjoint(e) (optionnel)</label>
                            <input v-model="form.spouse_name" type="text" placeholder="Nom du conjoint ou de la conjointe" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.spouse_name" class="mt-1 text-sm text-rose-600">{{ form.errors.spouse_name }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Photo du conjoint (optionnel)</label>
                            <input type="file" accept="image/*" :disabled="isOffline" @change="onSpousePhotoChange" class="block w-full text-sm text-graphite/73 file:mr-3 file:rounded-lg file:border-0 file:bg-graphite/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-graphite/87 hover:file:bg-graphite/20 disabled:opacity-50" />
                            <p class="mt-1 text-xs text-graphite/58" v-if="!isOffline">Image, 5 Mo maximum.</p>
                            <p class="mt-1 text-xs text-gold" v-else>Pas disponible hors connexion, tu pourras l'ajouter plus tard en modifiant la fiche.</p>
                            <p v-if="form.errors.spouse_photo" class="mt-1 text-sm text-rose-600">{{ form.errors.spouse_photo }}</p>
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
