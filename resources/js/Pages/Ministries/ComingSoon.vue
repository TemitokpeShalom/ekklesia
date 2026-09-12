<script setup>
import { Link } from '@inertiajs/vue3'

/**
 * Provisoire (2026-09-09) : le bouton "Créer un nouveau ministère" doit
 * exister sur la page d'accueil (voir Welcome.vue) puisque c'est un des
 * deux parcours attendus des l'arrivee sur l'application, mais la creation
 * libre-service d'un ministere touche a des questions non encore tranchees
 * avec l'utilisateur (validation manuelle ou non, essai gratuit automatique
 * - voir Ministry::TRIAL_DAYS/subscription_status). Cet ecran evite un lien
 * mort en attendant cette decision ; a remplacer par le vrai formulaire une
 * fois le parcours choisi.
 */
</script>

<template>
    <div class="app-shell min-h-screen flex items-center justify-center px-4 relative overflow-hidden bg-paper">
        <div class="absolute -top-24 -right-16 w-[28rem] h-[28rem] bg-sanctuary/8 rounded-full blur-[100px]" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-16 w-[28rem] h-[28rem] bg-gold/10 rounded-full blur-[100px]" aria-hidden="true"></div>
        <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[30rem] h-[30rem] opacity-[0.05] pointer-events-none hidden sm:block"
            viewBox="0 0 200 200" fill="#8f6a2c" aria-hidden="true">
            <path d="M90 10 H110 V80 H180 V100 H110 V190 H90 V100 H20 V80 H90 Z" />
        </svg>

        <div class="relative w-full max-w-md glass-panel p-8 rounded-3xl shadow-xl shadow-graphite/10 border-t-2 border-t-gold/70 animate-[fadeInUp_0.6s_ease-out_both] text-center">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-white p-1 shadow-glow-gold mb-5"><img src="/images/oikonema-icon.png" alt="OIKONEMA" class="w-full h-full object-contain rounded-xl" /></span>
            <h1 class="font-serif text-5xl font-bold text-graphite mb-3">Bientôt disponible</h1>
            <p class="text-sm text-graphite/70 mb-8">
                La création directe d'un nouveau ministère depuis cette page arrive prochainement.
                En attendant, contactez l'équipe Oikonema pour l'ouverture de votre espace.
            </p>
            <Link href="/bienvenue"
                class="w-full inline-flex items-center justify-center gap-1.5 bg-graphite/5 hover:bg-graphite/10 border border-graphite/15 transition-colors text-graphite rounded-xl py-3 font-semibold">
                Retour à l'accueil
            </Link>
        </div>
    </div>
</template>
