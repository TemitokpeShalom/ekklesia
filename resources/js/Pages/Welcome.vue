<script setup>
import { Link } from '@inertiajs/vue3'

/**
 * Page d'accueil publique (2026-09-09) - voir WelcomeController pour le
 * contexte. Reprend le meme decor "vitrail" que Login.vue pour que le tout
 * premier ecran de l'application soit visuellement coherent avec celui qui
 * suit juste apres (clic sur "Se connecter").
 */
</script>

<template>
    <div class="min-h-screen flex items-center justify-center px-4 relative overflow-hidden bg-night">
        <div class="absolute -top-24 -right-16 w-[28rem] h-[28rem] bg-sanctuary/26 rounded-full blur-[100px]" style="animation: driftGlow 14s ease-in-out infinite;" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-16 w-[28rem] h-[28rem] bg-gold/18 rounded-full blur-[100px]" style="animation: driftGlow 18s ease-in-out infinite reverse;" aria-hidden="true"></div>
        <div class="absolute top-1/3 right-1/4 w-80 h-80 bg-forest/22 rounded-full blur-[100px]" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-vitrail" aria-hidden="true"></div>

        <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[42rem] h-[42rem] opacity-[0.07] pointer-events-none hidden sm:block"
            viewBox="0 0 200 200" fill="none" stroke="#f1e4c8" stroke-width="0.7" aria-hidden="true"
            style="animation: shimmerPulse 9s ease-in-out infinite;">
            <circle cx="100" cy="100" r="78" />
            <circle cx="100" cy="100" r="60" />
            <circle cx="100" cy="100" r="16" />
            <g v-for="n in 16" :key="n" :transform="`rotate(${n * 22.5} 100 100)`">
                <line x1="100" y1="22" x2="100" y2="100" />
            </g>
        </svg>

        <div class="relative w-full max-w-md glass-panel p-8 rounded-3xl shadow-2xl shadow-black/40 border-t-2 border-t-gold/70 animate-[fadeInUp_0.6s_ease-out_both] text-center">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-gradient-to-br from-gold to-gold-dark text-night font-serif font-bold shadow-glow-gold mb-5">O</span>
            <p class="text-xs uppercase tracking-widest text-gold-soft/90 font-semibold mb-1">Bienvenue</p>
            <h1 class="font-serif text-3xl text-white mb-2">Oikonema</h1>
            <p class="text-sm text-white/60 mb-8">Plateforme de gestion de ministère.</p>

            <div class="space-y-3">
                <Link href="/connexion"
                    class="w-full inline-flex items-center justify-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-3 font-semibold shadow-lg shadow-gold/20">
                    Se connecter à mon espace de travail
                </Link>
                <Link href="/ministeres/nouveau"
                    class="w-full inline-flex items-center justify-center gap-1.5 bg-white/5 hover:bg-white/10 border border-white/15 transition-colors text-white rounded-xl py-3 font-semibold">
                    Créer un nouveau ministère
                </Link>
            </div>

            <p class="text-center text-sm text-white/50 mt-7">
                Vous avez reçu un code de rattachement ?
                <a href="/rattachement" class="text-gold-soft hover:underline font-medium">Rejoindre votre église</a>
            </p>
        </div>
    </div>
</template>
