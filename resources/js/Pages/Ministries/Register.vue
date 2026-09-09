<script setup>
import { useForm, Link } from '@inertiajs/vue3'

/**
 * Creation libre-service d'un nouveau ministere (2026-09-09) - voir
 * MinistryRegistrationController. short_code n'est volontairement pas
 * demande ici : c'est un identifiant technique interne
 * (Ministry::short_code), sans utilite pour la personne qui remplit ce
 * formulaire - il est genere automatiquement a partir du nom (voir
 * MinistryRegistrationService::uniqueShortCode).
 */
const form = useForm({
    name: '',
    account_name: '',
    account_email: '',
    account_phone: '',
    account_password: '',
    account_password_confirmation: '',
})

function submit() {
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
            class="relative w-full max-w-md glass-panel p-8 rounded-3xl shadow-2xl shadow-black/40 border-t-2 border-t-gold/70 animate-[fadeInUp_0.6s_ease-out_both]">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-gradient-to-br from-gold to-gold-dark text-night font-serif font-bold shadow-glow-gold mb-5">E</span>
            <p class="text-xs uppercase tracking-widest text-gold-soft/90 font-semibold mb-1">Nouveau ministère</p>
            <h1 class="font-serif text-2xl text-white mb-1">Créer votre espace</h1>
            <p class="text-sm text-white/60 mb-6">Votre ministère est créé immédiatement, avec un essai gratuit de 30 jours.</p>

            <label class="block text-sm font-medium text-white/80 mb-1" for="name">Nom du ministère</label>
            <input id="name" v-model="form.name" type="text" required placeholder="ex. Église du Réveil de Porto-Novo"
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.name" class="text-sm text-rose-400 mb-3">{{ form.errors.name }}</p>

            <p class="text-xs uppercase tracking-widest text-white/40 font-semibold mt-6 mb-3">Votre compte (titulaire)</p>

            <label class="block text-sm font-medium text-white/80 mb-1" for="account_name">Votre nom complet</label>
            <input id="account_name" v-model="form.account_name" type="text" required
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.account_name" class="text-sm text-rose-400 mb-3">{{ form.errors.account_name }}</p>

            <label class="block text-sm font-medium text-white/80 mb-1 mt-4" for="account_email">Adresse e-mail</label>
            <input id="account_email" v-model="form.account_email" type="email" required
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.account_email" class="text-sm text-rose-400 mb-3">{{ form.errors.account_email }}</p>

            <label class="block text-sm font-medium text-white/80 mb-1 mt-4" for="account_phone">Téléphone (optionnel)</label>
            <input id="account_phone" v-model="form.account_phone" type="text"
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.account_phone" class="text-sm text-rose-400 mb-3">{{ form.errors.account_phone }}</p>

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
