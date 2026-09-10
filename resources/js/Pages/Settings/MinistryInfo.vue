<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Informations officielles du ministere (2026-09-09) - voir
 * MinistryInfoController. Meme role, pour l'identite du ministere, que
 * "Titres honorifiques" pour la liste des titres : reglage unique par
 * ministere, ecran reserve a la racine de l'arbre.
 */
const props = defineProps({
    orgUnit: Object,
    ministry: Object,
})

const form = useForm({
    name: props.ministry.name,
    acronym: props.ministry.acronym ?? '',
    registration_number: props.ministry.registration_number ?? '',
    headquarters_address: props.ministry.headquarters_address ?? '',
    phone: props.ministry.phone ?? '',
    email: props.ministry.email ?? '',
    website: props.ministry.website ?? '',
    logo: null,
    remove_logo: false,
})

const logoPreview = ref(null)

function onLogoChange(event) {
    const file = event.target.files[0] ?? null
    form.logo = file
    form.remove_logo = false
    logoPreview.value = file ? URL.createObjectURL(file) : null
}

function removeLogo() {
    form.logo = null
    form.remove_logo = true
    logoPreview.value = null
}

function submit() {
    // Comme Members/Edit.vue pour la photo d'un membre : form.put() detecte
    // lui-meme la presence d'un fichier et bascule en FormData + spoofing
    // de methode, sans transformation manuelle necessaire.
    form.put(`/org-units/${props.orgUnit.id}/informations-ministere`, { preserveScroll: true })
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Paramètres
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">Informations du ministère</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">
                    Identité officielle du ministère (comme sur un dossier de reconnaissance de culte) : sigle, siège, coordonnées, numéro d'autorisation.
                </p>
            </div>
        </template>

        <div class="max-w-lg mx-auto">
            <form @submit.prevent="submit" class="glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <label class="block text-sm font-medium text-graphite/87 mb-1" for="name">Nom du ministère</label>
                <input id="name" v-model="form.name" type="text" required
                    class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 mb-1 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                <p v-if="form.errors.name" class="text-sm text-rose-600 mb-3">{{ form.errors.name }}</p>

                <div class="grid grid-cols-2 gap-3 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-graphite/87 mb-1" for="acronym">Sigle</label>
                        <input id="acronym" v-model="form.acronym" type="text"
                            class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        <p v-if="form.errors.acronym" class="mt-1 text-sm text-rose-600">{{ form.errors.acronym }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-graphite/87 mb-1" for="registration_number">N° d'autorisation</label>
                        <input id="registration_number" v-model="form.registration_number" type="text"
                            class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        <p v-if="form.errors.registration_number" class="mt-1 text-sm text-rose-600">{{ form.errors.registration_number }}</p>
                    </div>
                </div>

                <label class="block text-sm font-medium text-graphite/87 mb-1 mt-4" for="headquarters_address">Adresse du siège</label>
                <input id="headquarters_address" v-model="form.headquarters_address" type="text"
                    class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 mb-1 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                <p v-if="form.errors.headquarters_address" class="text-sm text-rose-600 mb-3">{{ form.errors.headquarters_address }}</p>

                <div class="grid grid-cols-2 gap-3 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-graphite/87 mb-1" for="phone">Téléphone</label>
                        <input id="phone" v-model="form.phone" type="text"
                            class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        <p v-if="form.errors.phone" class="mt-1 text-sm text-rose-600">{{ form.errors.phone }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-graphite/87 mb-1" for="email">E-mail</label>
                        <input id="email" v-model="form.email" type="email"
                            class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>
                    </div>
                </div>

                <label class="block text-sm font-medium text-graphite/87 mb-1 mt-4" for="website">Site web</label>
                <input id="website" v-model="form.website" type="text"
                    class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 mb-1 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                <p v-if="form.errors.website" class="text-sm text-rose-600 mb-3">{{ form.errors.website }}</p>

                <label class="block text-sm font-medium text-graphite/87 mb-1 mt-4">Logo</label>
                <div class="flex items-center gap-3">
                    <img v-if="logoPreview" :src="logoPreview" class="h-14 w-14 rounded-xl object-cover border border-graphite/15" />
                    <img v-else-if="ministry.logo_path && !form.remove_logo" :src="`/storage/${ministry.logo_path}`" class="h-14 w-14 rounded-xl object-cover border border-graphite/15" />
                    <input type="file" accept="image/*" @change="onLogoChange"
                        class="flex-1 text-sm text-graphite/80 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:bg-graphite/10 file:text-graphite file:text-sm file:font-medium hover:file:bg-graphite/15" />
                    <button v-if="(ministry.logo_path && !form.remove_logo) || logoPreview" type="button" @click="removeLogo"
                        class="shrink-0 w-9 h-9 flex items-center justify-center rounded-xl text-graphite/62 hover:text-rose-600 hover:bg-rose-500/10 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p v-if="form.errors.logo" class="mt-1 text-sm text-rose-600">{{ form.errors.logo }}</p>

                <button type="submit" :disabled="form.processing"
                    class="w-full mt-6 inline-flex items-center justify-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-2.5 font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
                    Enregistrer
                </button>
            </form>
        </div>
    </AppLayout>
</template>
