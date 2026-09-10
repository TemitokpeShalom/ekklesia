<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

/**
 * Creation libre-service d'un nouveau ministere (2026-09-09, complete le
 * meme jour) - voir MinistryRegistrationController. Reprend, en plus du
 * nom, ce que porte habituellement un dossier de reconnaissance de culte
 * aupres du Ministere de l'Interieur et de la Securite Publique (sigle,
 * siege, coordonnees, numero d'autorisation) et le logo - tout facultatif,
 * completable plus tard depuis le tableau de bord (voir
 * MinistryInfoController) une fois ce numero effectivement obtenu.
 */
const form = useForm({
    name: '',
    acronym: '',
    registration_number: '',
    headquarters_address: '',
    phone: '',
    email: '',
    website: '',
    logo: null,
    account_name: '',
    account_email: '',
    account_phone: '',
    account_password: '',
    account_password_confirmation: '',
})

const logoPreview = ref(null)

function onLogoChange(event) {
    const file = event.target.files[0] ?? null
    form.logo = file
    logoPreview.value = file ? URL.createObjectURL(file) : null
}

function submit() {
    // Comme Members/Create.vue pour la photo d'un membre : form.post()
    // detecte lui-meme la presence d'un fichier (form.logo) et bascule en
    // FormData automatiquement.
    form.post('/ministeres/nouveau', {
        onFinish: () => form.reset('account_password', 'account_password_confirmation'),
    })
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center px-4 py-10 relative overflow-hidden bg-night">
        <div class="absolute -top-24 -right-16 w-[28rem] h-[28rem] bg-sanctuary/26 rounded-full blur-[100px]" style="animation: driftGlow 14s ease-in-out infinite;" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-16 w-[28rem] h-[28rem] bg-gold/18 rounded-full blur-[100px]" style="animation: driftGlow 18s ease-in-out infinite reverse;" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-vitrail" aria-hidden="true"></div>

        <form @submit.prevent="submit"
            class="relative w-full max-w-lg glass-panel p-8 rounded-3xl shadow-2xl shadow-black/40 border-t-2 border-t-gold/70 animate-[fadeInUp_0.6s_ease-out_both]">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-gradient-to-br from-gold to-gold-dark text-night font-serif font-bold shadow-glow-gold mb-5">O</span>
            <p class="text-xs uppercase tracking-widest text-gold-soft/90 font-semibold mb-1">Nouveau ministère</p>
            <h1 class="font-serif text-2xl text-white mb-1">Créer votre espace</h1>
            <p class="text-sm text-white/60 mb-6">Votre ministère est créé immédiatement, avec un essai gratuit de 30 jours.</p>

            <p class="text-xs uppercase tracking-widest text-white/40 font-semibold mb-3">Informations officielles du ministère</p>

            <label class="block text-sm font-medium text-white/80 mb-1" for="name">Nom du ministère</label>
            <input id="name" v-model="form.name" type="text" required placeholder="ex. Église du Réveil de Porto-Novo"
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.name" class="text-sm text-rose-400 mb-3">{{ form.errors.name }}</p>

            <div class="grid grid-cols-2 gap-3 mt-4">
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-1" for="acronym">Sigle (si le ministère en a un)</label>
                    <input id="acronym" v-model="form.acronym" type="text" placeholder="ex. ERPN"
                        class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.acronym" class="mt-1 text-sm text-rose-400">{{ form.errors.acronym }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-1" for="registration_number">N° d'autorisation</label>
                    <input id="registration_number" v-model="form.registration_number" type="text" placeholder="délivré par le Ministère de l'Intérieur"
                        class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.registration_number" class="mt-1 text-sm text-rose-400">{{ form.errors.registration_number }}</p>
                </div>
            </div>
            <p class="text-xs text-white/35 mt-1.5">
                Pas encore obtenu ? Laissez vide, vous pourrez le renseigner plus tard depuis le tableau de bord.
            </p>

            <label class="block text-sm font-medium text-white/80 mb-1 mt-4" for="headquarters_address">Adresse du siège</label>
            <input id="headquarters_address" v-model="form.headquarters_address" type="text"
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.headquarters_address" class="text-sm text-rose-400 mb-3">{{ form.errors.headquarters_address }}</p>

            <div class="grid grid-cols-2 gap-3 mt-4">
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-1" for="phone">Téléphone du ministère</label>
                    <input id="phone" v-model="form.phone" type="text"
                        class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.phone" class="mt-1 text-sm text-rose-400">{{ form.errors.phone }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-1" for="email">E-mail du ministère</label>
                    <input id="email" v-model="form.email" type="email"
                        class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-rose-400">{{ form.errors.email }}</p>
                </div>
            </div>

            <label class="block text-sm font-medium text-white/80 mb-1 mt-4" for="website">Site web (optionnel)</label>
            <input id="website" v-model="form.website" type="text" placeholder="ex. www.mon-eglise.bj"
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.website" class="text-sm text-rose-400 mb-3">{{ form.errors.website }}</p>

            <label class="block text-sm font-medium text-white/80 mb-1 mt-4">Logo (optionnel)</label>
            <div class="flex items-center gap-3">
                <img v-if="logoPreview" :src="logoPreview" class="h-14 w-14 rounded-xl object-cover border border-white/15" />
                <input type="file" accept="image/*" @change="onLogoChange"
                    class="flex-1 text-sm text-white/70 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:bg-white/10 file:text-white file:text-sm file:font-medium hover:file:bg-white/15" />
            </div>
            <p v-if="form.errors.logo" class="mt-1 text-sm text-rose-400">{{ form.errors.logo }}</p>

            <p class="text-xs uppercase tracking-widest text-white/40 font-semibold mt-7 mb-3">Votre compte (fondateur)</p>

            <label class="block text-sm font-medium text-white/80 mb-1" for="account_name">Nom et prénom</label>
            <input id="account_name" v-model="form.account_name" type="text" required
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.account_name" class="text-sm text-rose-400 mb-3">{{ form.errors.account_name }}</p>

            <div class="grid grid-cols-2 gap-3 mt-4">
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-1" for="account_email">Votre e-mail</label>
                    <input id="account_email" v-model="form.account_email" type="email" required
                        class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.account_email" class="mt-1 text-sm text-rose-400">{{ form.errors.account_email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-1" for="account_phone">Votre téléphone</label>
                    <input id="account_phone" v-model="form.account_phone" type="text"
                        class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.account_phone" class="mt-1 text-sm text-rose-400">{{ form.errors.account_phone }}</p>
                </div>
            </div>

            <label class="block text-sm font-medium text-white/80 mb-1 mt-4" for="account_password">Mot de passe</label>
            <input id="account_password" v-model="form.account_password" type="password" required minlength="8"
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.account_password" class="text-sm text-rose-400 mb-3">{{ form.errors.account_password }}</p>

            <label class="block text-sm font-medium text-white/80 mb-1 mt-4" for="account_password_confirmation">Confirmer le mot de passe</label>
            <input id="account_password_confirmation" v-model="form.account_password_confirmation" type="password" required minlength="8"
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />

            <button type="submit" :disabled="form.processing"
                class="w-full mt-6 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-3 font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
                Créer mon ministère
            </button>

            <p class="text-center text-sm text-white/50 mt-6">
                <Link href="/bienvenue" class="text-gold-soft hover:underline font-medium">Retour à l'accueil</Link>
            </p>
        </form>
    </div>
</template>
