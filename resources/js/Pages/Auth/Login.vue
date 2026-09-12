<script setup>
import { useForm } from '@inertiajs/vue3';

/**
 * v6 "Vitrine claire" (2026-09-10) : le ministere a fourni son propre logo
 * (globe/croix/agneau/livre, bleu et or) et a demande un fond blanc partout,
 * y compris sur les ecrans publics jusque-la restes sombres ("Vitrail",
 * v3/v5) - avec une silhouette de croix en filigrane a la place de la
 * rosace. Le composant `.app-shell` (deja utilise pour tout l'outil de
 * travail depuis "Constellation") est reutilise ici tel quel : meme carte
 * blanche (`glass-panel`), meme fond `bg-paper` - la meme structure de
 * formulaire et les memes routes qu'avant (rien ne change cote fonctionnel).
 */
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/connexion', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div class="app-shell min-h-screen flex items-center justify-center px-4 relative overflow-hidden bg-paper">
        <!-- Halos tres doux, tons de la marque (2026-09-10, fond blanc) -->
        <div class="absolute -top-24 -right-16 w-[28rem] h-[28rem] bg-sanctuary/8 rounded-full blur-[100px]" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-16 w-[28rem] h-[28rem] bg-gold/10 rounded-full blur-[100px]" aria-hidden="true"></div>

        <!-- Croix en filigrane (remplace la rosace "Vitrail" sombre - demande du ministere, 2026-09-10) -->
        <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[30rem] h-[30rem] opacity-[0.05] pointer-events-none hidden sm:block"
            viewBox="0 0 200 200" fill="#8f6a2c" aria-hidden="true">
            <path d="M90 10 H110 V80 H180 V100 H110 V190 H90 V100 H20 V80 H90 Z" />
        </svg>

        <form @submit.prevent="submit"
            class="relative w-full max-w-sm glass-panel p-8 rounded-3xl shadow-xl shadow-graphite/10 border-t-2 border-t-gold/70 animate-[fadeInUp_0.6s_ease-out_both]">
            <img src="/images/oikonema-logo.png" alt="OIKONEMA" class="w-40 sm:w-44 mx-auto mb-3 rounded-2xl bg-white p-2 shadow-glow-gold" />
            <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold mb-5 text-center">Bienvenue</p>
            <p class="text-sm text-graphite/70 mb-7 -mt-2 text-center">Plateforme de gestion de ministère : connectez-vous à votre espace.</p>

            <label class="block text-sm font-medium text-graphite/85 mb-1" for="email">Adresse e-mail</label>
            <input id="email" v-model="form.email" type="email" required autofocus
                class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/35 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.email" class="text-sm text-rose-600 mb-3">{{ form.errors.email }}</p>

            <label class="block text-sm font-medium text-graphite/85 mb-1 mt-4" for="password">Mot de passe</label>
            <input id="password" v-model="form.password" type="password" required
                class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/35 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.password" class="text-sm text-rose-600 mb-3">{{ form.errors.password }}</p>

            <label class="flex items-center gap-2 text-sm text-graphite/70 my-5">
                <input type="checkbox" v-model="form.remember" class="rounded border-graphite/25 bg-graphite/5 text-gold focus:ring-gold/50" />
                Se souvenir de moi
            </label>

            <!-- Corrige le 2026-09-12 : bouton de commande passe de l'or au bleu marine du logo (voir tailwind.config.js, couleur "azure") -- demande explicite du ministere. -->
            <button type="submit" :disabled="form.processing"
                class="w-full bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl py-3 font-semibold shadow-lg shadow-azure/20 disabled:opacity-60">
                Se connecter
            </button>

            <!--
                Ajoute le 2026-09-09 : avant, un nouvel arrivant qui recevait un
                code de rattachement (et n'a donc pas encore de compte) n'avait
                aucun moyen de le decouvrir depuis cette page - il fallait
                connaitre l'adresse /rattachement par coeur. C'est pourtant le
                tout premier ecran que voit quiconque n'est pas deja connecte.
            -->
            <p class="text-center text-sm text-graphite/60 mt-6">
                Vous avez reçu un code de rattachement ?
                <a href="/rattachement" class="text-sanctuary hover:underline font-medium">Rejoindre votre église</a>
            </p>
        </form>
    </div>
</template>
