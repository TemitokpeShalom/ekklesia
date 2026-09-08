<script setup>
import { useForm } from '@inertiajs/vue3';

/**
 * v3 "Vitrail" (2026-09-08) : premiere impression de la plateforme, donc
 * l'ecran le plus visible pour juger si l'identite reste "trop classique".
 * Fond nuit chaleureux (jamais un noir froid), rosace en filigrane,
 * carte en verre depoli plutot que carte blanche opaque -- la meme
 * structure de formulaire et les memes routes qu'avant (rien ne change
 * cote fonctionnel).
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
    <div class="min-h-screen flex items-center justify-center px-4 relative overflow-hidden bg-night">
        <!-- Halos qui derivent tres lentement (eclaircis, plus verts le 2026-09-09) -->
        <div class="absolute -top-24 -right-16 w-[28rem] h-[28rem] bg-sanctuary/26 rounded-full blur-[100px]" style="animation: driftGlow 14s ease-in-out infinite;" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-16 w-[28rem] h-[28rem] bg-gold/18 rounded-full blur-[100px]" style="animation: driftGlow 18s ease-in-out infinite reverse;" aria-hidden="true"></div>
        <div class="absolute top-1/3 right-1/4 w-80 h-80 bg-forest/22 rounded-full blur-[100px]" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-vitrail" aria-hidden="true"></div>

        <!-- Rosace : mullions rayonnants + arche, motif "vitrail" en filigrane -->
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

        <!-- Arche gothique, deja utilisee en v2, reprise et amplifiee -->
        <svg class="absolute right-[-70px] bottom-[-40px] w-[30rem] h-[30rem] opacity-[0.1] pointer-events-none hidden md:block"
            viewBox="0 0 200 200" fill="none" stroke="#b98a3e" stroke-width="1.3" aria-hidden="true">
            <path d="M20 150 C60 135,90 135,100 145 C110 135,140 135,180 150 L180 160 C140 145,110 145,100 155 C90 145,60 145,20 160 Z" />
            <path d="M100 145 L100 155" />
            <path d="M30 145 C55 133,80 133,95 141" />
            <path d="M170 145 C145 133,120 133,105 141" />
            <path d="M100 38 L100 92" />
            <path d="M76 58 L124 58" />
            <circle cx="100" cy="95" r="46" stroke-opacity="0.5" />
        </svg>

        <form @submit.prevent="submit"
            class="relative w-full max-w-sm glass-panel p-8 rounded-3xl shadow-2xl shadow-black/40 border-t-2 border-t-gold/70 animate-[fadeInUp_0.6s_ease-out_both]">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-gradient-to-br from-gold to-gold-dark text-night font-serif font-bold shadow-glow-gold mb-5">E</span>
            <p class="text-xs uppercase tracking-widest text-gold-soft/90 font-semibold mb-1">Bienvenue</p>
            <h1 class="font-serif text-3xl text-white mb-1">Ekklesia</h1>
            <p class="text-sm text-white/60 mb-7">Plateforme de gestion de ministère : connectez-vous à votre espace.</p>

            <label class="block text-sm font-medium text-white/80 mb-1" for="email">Adresse e-mail</label>
            <input id="email" v-model="form.email" type="email" required autofocus
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.email" class="text-sm text-rose-400 mb-3">{{ form.errors.email }}</p>

            <label class="block text-sm font-medium text-white/80 mb-1 mt-4" for="password">Mot de passe</label>
            <input id="password" v-model="form.password" type="password" required
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.password" class="text-sm text-rose-400 mb-3">{{ form.errors.password }}</p>

            <label class="flex items-center gap-2 text-sm text-white/60 my-5">
                <input type="checkbox" v-model="form.remember" class="rounded border-white/25 bg-white/5 text-gold focus:ring-gold/50" />
                Se souvenir de moi
            </label>

            <button type="submit" :disabled="form.processing"
                class="w-full bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-3 font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
                Se connecter
            </button>
        </form>
    </div>
</template>
