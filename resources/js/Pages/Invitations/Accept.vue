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
    <div class="app-shell min-h-screen flex items-center justify-center px-4 relative overflow-hidden bg-paper">
        <div class="absolute -top-24 -right-16 w-[28rem] h-[28rem] bg-sanctuary/8 rounded-full blur-[100px]" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-16 w-[28rem] h-[28rem] bg-gold/10 rounded-full blur-[100px]" aria-hidden="true"></div>

        <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[30rem] h-[30rem] opacity-[0.05] pointer-events-none hidden sm:block"
            viewBox="0 0 200 200" fill="#8f6a2c" aria-hidden="true">
            <path d="M90 10 H110 V80 H180 V100 H110 V190 H90 V100 H20 V80 H90 Z" />
        </svg>

        <div v-if="!valid"
            class="relative w-full max-w-sm glass-panel p-8 rounded-3xl shadow-xl shadow-graphite/10 border-t-2 border-t-rose-400/70 animate-[fadeInUp_0.6s_ease-out_both] text-center">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 mb-5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </span>
            <h1 class="font-serif text-2xl text-graphite mb-2">Invitation invalide</h1>
            <p class="text-sm text-graphite/70">{{ error }}</p>
        </div>

        <form v-else @submit.prevent="submit"
            class="relative w-full max-w-sm glass-panel p-8 rounded-3xl shadow-xl shadow-graphite/10 border-t-2 border-t-gold/70 animate-[fadeInUp_0.6s_ease-out_both]">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-white p-1 shadow-glow-gold mb-5"><img src="/images/oikonema-icon.png" alt="OIKONEMA" class="w-full h-full object-contain rounded-xl" /></span>
            <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold mb-1">Bienvenue</p>
            <h1 class="font-serif text-2xl text-graphite mb-1">Créer votre compte</h1>
            <p class="text-sm text-graphite/70 mb-7">
                Ce compte est personnel : il vous appartient, même si votre poste change plus tard.
            </p>

            <p v-if="form.errors.invitation" class="text-sm text-rose-600 mb-4 rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5">
                {{ form.errors.invitation }}
            </p>

            <label class="block text-sm font-medium text-graphite/85 mb-1">Nom complet</label>
            <input v-model="form.name" required class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.name" class="text-sm text-rose-600 mb-3 -mt-2">{{ form.errors.name }}</p>

            <label class="block text-sm font-medium text-graphite/85 mb-1">Adresse e-mail</label>
            <input v-model="form.email" type="email" required class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.email" class="text-sm text-rose-600 mb-3 -mt-2">{{ form.errors.email }}</p>

            <label class="block text-sm font-medium text-graphite/85 mb-1">Téléphone (optionnel)</label>
            <input v-model="form.phone" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />

            <label class="block text-sm font-medium text-graphite/85 mb-1">Mot de passe</label>
            <input v-model="form.password" type="password" required class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            <p v-if="form.errors.password" class="text-sm text-rose-600 mb-3 -mt-2">{{ form.errors.password }}</p>

            <label class="block text-sm font-medium text-graphite/85 mb-1">Confirmer le mot de passe</label>
            <input v-model="form.password_confirmation" type="password" required class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 mb-5 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />

            <button type="submit" :disabled="form.processing"
                class="w-full bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-3 font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
                Créer mon compte
            </button>
        </form>
    </div>
</template>
