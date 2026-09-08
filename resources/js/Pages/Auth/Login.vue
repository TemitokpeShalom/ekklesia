<script setup>
import { useForm } from '@inertiajs/vue3';

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
    <div class="min-h-screen flex items-center justify-center px-4 relative overflow-hidden bg-gradient-to-br from-sanctuary-dark via-sanctuary to-coffee-dark">
        <div class="absolute -top-24 -right-16 w-96 h-96 bg-gold/30 rounded-full blur-3xl" style="animation: driftGlow 14s ease-in-out infinite;" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-16 w-96 h-96 bg-slateblue/25 rounded-full blur-3xl" style="animation: driftGlow 18s ease-in-out infinite reverse;" aria-hidden="true"></div>
        <div class="absolute top-1/3 right-1/4 w-64 h-64 bg-forest/15 rounded-full blur-3xl" aria-hidden="true"></div>

        <svg class="absolute right-[-60px] top-1/2 -translate-y-1/2 w-[26rem] h-[26rem] opacity-[0.08] pointer-events-none hidden sm:block"
            viewBox="0 0 200 200" fill="none" stroke="white" stroke-width="1.4" aria-hidden="true">
            <path d="M20 150 C60 135,90 135,100 145 C110 135,140 135,180 150 L180 160 C140 145,110 145,100 155 C90 145,60 145,20 160 Z" />
            <path d="M100 145 L100 155" />
            <path d="M30 145 C55 133,80 133,95 141" />
            <path d="M30 152 C55 140,80 140,95 148" />
            <path d="M170 145 C145 133,120 133,105 141" />
            <path d="M170 152 C145 140,120 140,105 148" />
            <path d="M100 38 L100 92" />
            <path d="M76 58 L124 58" />
            <circle cx="100" cy="95" r="46" stroke-opacity="0.5" />
        </svg>

        <form @submit.prevent="submit"
            class="relative w-full max-w-sm bg-white/95 backdrop-blur p-8 rounded-3xl shadow-2xl shadow-sanctuary-dark/40 border-t-4 border-gold animate-[fadeInUp_0.6s_ease-out_both]">
            <p class="text-xs uppercase tracking-widest text-gold-dark font-semibold mb-1">Bienvenue</p>
            <h1 class="font-serif text-3xl text-ink mb-1">Ekklesia</h1>
            <p class="text-sm text-coffee-light mb-7">Connectez-vous à votre espace.</p>

            <label class="block text-sm font-medium text-ink mb-1" for="email">Adresse e-mail</label>
            <input id="email" v-model="form.email" type="email" required autofocus
                class="w-full border border-coffee/20 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-sanctuary/40 focus:border-sanctuary" />
            <p v-if="form.errors.email" class="text-sm text-red-600 mb-3">{{ form.errors.email }}</p>

            <label class="block text-sm font-medium text-ink mb-1 mt-4" for="password">Mot de passe</label>
            <input id="password" v-model="form.password" type="password" required
                class="w-full border border-coffee/20 rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-sanctuary/40 focus:border-sanctuary" />
            <p v-if="form.errors.password" class="text-sm text-red-600 mb-3">{{ form.errors.password }}</p>

            <label class="flex items-center gap-2 text-sm text-coffee my-5">
                <input type="checkbox" v-model="form.remember" class="rounded border-coffee/30 text-sanctuary focus:ring-sanctuary/40" />
                Se souvenir de moi
            </label>

            <button type="submit" :disabled="form.processing"
                class="w-full bg-gradient-to-r from-sanctuary to-sanctuary-dark hover:from-sanctuary-dark hover:to-sanctuary-dark transition-all duration-300 text-white rounded-xl py-3 font-medium shadow-lg shadow-sanctuary/30 disabled:opacity-60">
                Se connecter
            </button>
        </form>
    </div>
</template>
