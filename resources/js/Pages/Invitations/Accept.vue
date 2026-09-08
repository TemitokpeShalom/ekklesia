<script setup>
import { useForm } from '@inertiajs/vue3';

/**
 * v3 "Vitrail" (2026-09-09) : meme habillage que l'ecran de connexion
 * (fond nuit, rosace en filigrane, carte en verre depoli), sans aucun
 * changement fonctionnel.
 */
const props = defineProps({ token: String, valid: Boolean, error: String });

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(`/invitations/${props.token}`);
}
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

        <div v-if="!valid"
            class="relative w-full max-w-sm glass-panel p-8 rounded-3xl shadow-2xl shadow-black/40 border-t-2 border-t-rose-400/70 animate-[fadeInUp_0.6s_ease-out_both] text-center">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-rose-400/15 text-rose-300 mb-5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </span>
            <h1 class="font-serif text-2xl text-white mb-2">Invitation invalide</h1>
            <p class="text-sm text-white/60">{{ error }}</p>
        </div>

        <form v-else @submit.prevent="submit"
            class="relative w-full max-w-sm glass-panel p-8 rounded-3xl shadow-2xl shadow-black/40 border-t-2 border-t-gold/70 animate-[fadeInUp_0.6s_ease-out_both]">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-gradient-to-br from-gold to-gold-dark text-night font-serif font-bold shadow-glow-gold mb-5">E</span>
            <p class="text-xs uppercase tracking-widest text-gold-soft/90 font-semibold mb-1">Bienvenue</p>
            <h1 class="font-serif text-2xl text-white mb-1">Créer votre compte</h1>
            <p class="text-sm text-white/60 mb-7">
                Ce compte est personnel : il vous appartient, même si votre poste change plus tard.
            </p>

            <p v-if="form.errors.invitation" class="text-sm text-rose-400 mb-4 rounded-xl bg-rose-400/10 border border-rose-400/30 px-3.5 py-2.5">
                {{ form.errors.invitation }}
            </p>

            <label class="block text-sm font-medium text-white/80 mb-1">Nom complet</label>
            <input v-model="form.name" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.name" class="text-sm text-rose-400 mb-3 -mt-2">{{ form.errors.name }}</p>

            <label class="block text-sm font-medium text-white/80 mb-1">Adresse e-mail</label>
            <input v-model="form.email" type="email" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.email" class="text-sm text-rose-400 mb-3 -mt-2">{{ form.errors.email }}</p>

            <label class="block text-sm font-medium text-white/80 mb-1">Téléphone (optionnel)</label>
            <input v-model="form.phone" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />

            <label class="block text-sm font-medium text-white/80 mb-1">Mot de passe</label>
            <input v-model="form.password" type="password" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.password" class="text-sm text-rose-400 mb-3 -mt-2">{{ form.errors.password }}</p>

            <label class="block text-sm font-medium text-white/80 mb-1">Confirmer le mot de passe</label>
            <input v-model="form.password_confirmation" type="password" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-5 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />

            <button type="submit" :disabled="form.processing"
                class="w-full bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-3 font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
                Créer mon compte
            </button>
        </form>
    </div>
</template>
