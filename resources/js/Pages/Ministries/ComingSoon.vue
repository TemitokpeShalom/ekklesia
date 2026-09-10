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
    <div class="min-h-screen flex items-center justify-center px-4 relative overflow-hidden bg-night">
        <div class="absolute -top-24 -right-16 w-[28rem] h-[28rem] bg-sanctuary/26 rounded-full blur-[100px]" style="animation: driftGlow 14s ease-in-out infinite;" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-16 w-[28rem] h-[28rem] bg-gold/18 rounded-full blur-[100px]" style="animation: driftGlow 18s ease-in-out infinite reverse;" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-vitrail" aria-hidden="true"></div>

        <div class="relative w-full max-w-md glass-panel p-8 rounded-3xl shadow-2xl shadow-black/40 border-t-2 border-t-gold/70 animate-[fadeInUp_0.6s_ease-out_both] text-center">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-white p-1 shadow-glow-gold mb-5"><img src="/images/oikonema-icon.png" alt="OIKONEMA" class="w-full h-full object-contain rounded-xl" /></span>
            <h1 class="font-serif text-2xl text-white mb-3">Bientôt disponible</h1>
            <p class="text-sm text-white/60 mb-8">
                La création directe d'un nouveau ministère depuis cette page arrive prochainement.
                En attendant, contactez l'équipe Oikonema pour l'ouverture de votre espace.
            </p>
            <Link href="/bienvenue"
                class="w-full inline-flex items-center justify-center gap-1.5 bg-white/5 hover:bg-white/10 border border-white/15 transition-colors text-white rounded-xl py-3 font-semibold">
                Retour à l'accueil
            </Link>
        </div>
    </div>
</template>
