<script setup>
import { useForm } from '@inertiajs/vue3'

/**
 * Creation du compte d'un membre de l'equipe technique Oikonema
 * (2026-09-13, voir TechnicalAccountController) : reservee aux adresses
 * deja autorisees sur le serveur (TECHNICAL_STAFF_EMAILS) - ce formulaire
 * ne fait que poser un mot de passe sur une adresse deja approuvee, il
 * n'attache ce compte a AUCUN ministere (a la difference de toutes les
 * autres inscriptions de la plateforme). Meme habillage public "Vitrine
 * claire" que Auth/Login.vue, volontairement hors de la coquille
 * AppLayout (personne n'est encore connecte a ce stade).
 */
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

function submit() {
    form.post('/equipe-technique/creer-mon-compte', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <div class="app-shell min-h-screen flex items-center justify-center px-4 relative overflow-hidden bg-paper">
        <div class="absolute -top-24 -right-16 w-[28rem] h-[28rem] bg-sanctuary/8 rounded-full blur-[100px]" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-16 w-[28rem] h-[28rem] bg-gold/10 rounded-full blur-[100px]" aria-hidden="true"></div>

        <form @submit.prevent="submit"
            class="relative w-full max-w-sm glass-panel p-8 rounded-3xl shadow-xl shadow-graphite/10 border-t-2 border-t-azure/70 animate-[fadeInUp_0.6s_ease-out_both]">
            <img src="/images/oikonema-logo.png" alt="OIKONEMA" class="w-40 sm:w-44 mx-auto mb-3 rounded-2xl bg-white p-2 shadow-glow-gold" />
            <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold mb-5 text-center">Équipe technique</p>
            <p class="text-sm text-graphite/70 mb-7 -mt-2 text-center">Crée ton compte technique. Réservé aux adresses déjà autorisées sur le serveur.</p>

            <label class="block text-sm font-medium text-graphite/85 mb-1" for="name">Nom</label>
            <input id="name" v-model="form.name" type="text" required autofocus
                class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/35 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-azure/50 focus:border-azure/60" />
            <p v-if="form.errors.name" class="text-sm text-rose-600 mb-3">{{ form.errors.name }}</p>

            <label class="block text-sm font-medium text-graphite/85 mb-1 mt-4" for="email">Adresse e-mail</label>
            <input id="email" v-model="form.email" type="email" required
                class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/35 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-azure/50 focus:border-azure/60" />
            <p v-if="form.errors.email" class="text-sm text-rose-600 mb-3">{{ form.errors.email }}</p>

            <label class="block text-sm font-medium text-graphite/85 mb-1 mt-4" for="password">Mot de passe</label>
            <input id="password" v-model="form.password" type="password" required
                class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/35 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-azure/50 focus:border-azure/60" />
            <p v-if="form.errors.password" class="text-sm text-rose-600 mb-3">{{ form.errors.password }}</p>

            <label class="block text-sm font-medium text-graphite/85 mb-1 mt-4" for="password_confirmation">Confirme le mot de passe</label>
            <input id="password_confirmation" v-model="form.password_confirmation" type="password" required
                class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/35 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-azure/50 focus:border-azure/60" />

            <button type="submit" :disabled="form.processing"
                class="w-full bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl py-3 font-semibold shadow-lg shadow-azure/20 disabled:opacity-60 mt-5">
                Créer mon compte
            </button>

            <p class="text-center text-sm text-graphite/60 mt-6">
                Tu as déjà un compte ?
                <a href="/connexion" class="text-sanctuary hover:underline font-medium">Se connecter</a>
            </p>
        </form>
    </div>
</template>
