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
    member: Object,
    honorificTitles: Array,
})

const form = useForm({
    first_name: props.member.first_name,
    last_name: props.member.last_name,
    title: props.member.title,
    phone: props.member.phone,
    email: props.member.email,
    gender: props.member.gender,
    birth_date: props.member.birth_date,
    joined_at: props.member.joined_at,
    status: props.member.status,
    photo: null,
    remove_photo: false,
    spouse_name: props.member.spouse_name,
    spouse_photo: null,
    remove_spouse_photo: false,
})

const removePhoto = ref(false)
const removeSpousePhoto = ref(false)

function onPhotoChange(event) {
    form.photo = event.target.files[0] ?? null
}

function onSpousePhotoChange(event) {
    form.spouse_photo = event.target.files[0] ?? null
}

function toggleRemovePhoto() {
    removePhoto.value = !removePhoto.value
    form.remove_photo = removePhoto.value
}

function toggleRemoveSpousePhoto() {
    removeSpousePhoto.value = !removeSpousePhoto.value
    form.remove_spouse_photo = removeSpousePhoto.value
}

function submit() {
    form.put(`/org-units/${props.orgUnit.id}/membres/${props.member.id}`)
}

function destroy() {
    if (confirm('Retirer définitivement ce membre ?')) {
        router.delete(`/org-units/${props.orgUnit.id}/membres/${props.member.id}`)
    }
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/membres`" back-label="Annuler">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Membres
                </p>
                <h1 class="font-serif text-3xl text-white mt-2">Modifier {{ member.first_name }} {{ member.last_name }}</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]" enctype="multipart/form-data">
                <section>
                    <h2 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest mb-4">Identité</h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-white/80">Prénom</label>
                                <input v-model="form.first_name" type="text" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-white/80">Nom</label>
                                <input v-model="form.last_name" type="text" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Titre honorifique</label>
                            <select v-model="form.title" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option value="" class="bg-night text-white">Aucun</option>
                                <option v-for="t in honorificTitles" :key="t" :value="t" class="bg-night text-white">{{ t }}</option>
                            </select>
                            <p v-if="form.errors.title" class="mt-1 text-sm text-rose-400">{{ form.errors.title }}</p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-white/10 pt-6">
                    <h2 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest mb-4">Contact</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Téléphone</label>
                            <input v-model="form.phone" type="text" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Email</label>
                            <input v-model="form.email" type="email" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                    </div>
                </section>

                <section class="border-t border-white/10 pt-6">
                    <h2 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest mb-4">Détails</h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-white/80">Genre</label>
                                <select v-model="form.gender" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                    <option value="" class="bg-night text-white">Non précisé</option>
                                    <option value="M" class="bg-night text-white">Homme</option>
                                    <option value="F" class="bg-night text-white">Femme</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-white/80">Date de naissance</label>
                                <input v-model="form.birth_date" type="date" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Date d'adhésion</label>
                            <input v-model="form.joined_at" type="date" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Statut</label>
                            <select v-model="form.status" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option value="active" class="bg-night text-white">Actif</option>
                                <option value="inactive" class="bg-night text-white">Inactif</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="border-t border-white/10 pt-6">
                    <h2 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest mb-4">Photos et conjoint(e)</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Photo</label>
                            <div v-if="member.photo_path && !removePhoto" class="mb-2 flex items-center gap-3">
                                <img :src="`/storage/${member.photo_path}`" class="h-14 w-14 rounded-full object-cover border border-white/15" />
                                <button type="button" @click="toggleRemovePhoto" class="text-xs text-rose-400 hover:underline">Retirer la photo</button>
                            </div>
                            <p v-else-if="removePhoto" class="mb-2 text-xs text-white/40">
                                Photo retirée après enregistrement.
                                <button type="button" @click="toggleRemovePhoto" class="text-white/70 hover:underline">Annuler</button>
                            </p>
                            <input type="file" accept="image/*" @change="onPhotoChange" class="block w-full text-sm text-white/60 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white/80 hover:file:bg-white/20" />
                            <p class="mt-1 text-xs text-white/35">Image, 5 Mo maximum.</p>
                            <p v-if="form.errors.photo" class="mt-1 text-sm text-rose-400">{{ form.errors.photo }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Conjoint(e) (optionnel)</label>
                            <input v-model="form.spouse_name" type="text" placeholder="Nom du conjoint ou de la conjointe" class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-white/80">Photo du conjoint</label>
                            <div v-if="member.spouse_photo_path && !removeSpousePhoto" class="mb-2 flex items-center gap-3">
                                <img :src="`/storage/${member.spouse_photo_path}`" class="h-14 w-14 rounded-full object-cover border border-white/15" />
                                <button type="button" @click="toggleRemoveSpousePhoto" class="text-xs text-rose-400 hover:underline">Retirer la photo</button>
                            </div>
                            <p v-else-if="removeSpousePhoto" class="mb-2 text-xs text-white/40">
                                Photo retirée après enregistrement.
                                <button type="button" @click="toggleRemoveSpousePhoto" class="text-white/70 hover:underline">Annuler</button>
                            </p>
                            <input type="file" accept="image/*" @change="onSpousePhotoChange" class="block w-full text-sm text-white/60 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white/80 hover:file:bg-white/20" />
                            <p class="mt-1 text-xs text-white/35">Image, 5 Mo maximum.</p>
                            <p v-if="form.errors.spouse_photo" class="mt-1 text-sm text-rose-400">{{ form.errors.spouse_photo }}</p>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-between border-t border-white/10 pt-6">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Enregistrer
                    </button>
                    <button
                        type="button"
                        @click="destroy"
                        class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-medium text-rose-400 transition hover:bg-rose-500/10"
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
