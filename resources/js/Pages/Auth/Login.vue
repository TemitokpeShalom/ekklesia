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
        <svg class="absolute inset-0 h-full w-full opacity-10" preserveAspectRatio="none" viewBox="0 0 400 400" aria-hidden="true">
            <defs>
                <pattern id="archesLogin" width="50" height="100" patternUnits="userSpaceOnUse">
                    <path d="M0,100 L0,55 A25,25 0 0 1 50,55 L50,100 Z" fill="none" stroke="white" stroke-width="2" />
                </pattern>
            </defs>
            <rect width="400" height="400" fill="url(#archesLogin)" />
        </svg>

        <form @submit.prevent="submit" class="relative w-full max-w-sm bg-white p-8 rounded-2xl shadow-xl border-t-4 border-gold">
            <p class="text-xs uppercase tracking-widest text-gold-dark font-semibold mb-1">Bienvenue</p>
            <h1 class="font-serif text-2xl text-ink mb-1">Ekklesia</h1>
            <p class="text-sm text-coffee-light mb-6">Connectez-vous à votre espace.</p>

            <label class="block text-sm font-medium text-ink mb-1" for="email">Adresse e-mail</label>
            <input id="email" v-model="form.email" type="email" required autofocus
                class="w-full border border-coffee/20 rounded-lg px-3 py-2 mb-1 focus:outline-none focus:ring-2 focus:ring-sanctuary/40 focus:border-sanctuary" />
            <p v-if="form.errors.email" class="text-sm text-red-600 mb-3">{{ form.errors.email }}</p>

            <label class="block text-sm font-medium text-ink mb-1 mt-3" for="password">Mot de passe</label>
            <input id="password" v-model="form.password" type="password" required
                class="w-full border border-coffee/20 rounded-lg px-3 py-2 mb-1 focus:outline-none focus:ring-2 focus:ring-sanctuary/40 focus:border-sanctuary" />
            <p v-if="form.errors.password" class="text-sm text-red-600 mb-3">{{ form.errors.password }}</p>

            <label class="flex items-center gap-2 text-sm text-coffee my-4">
                <input type="checkbox" v-model="form.remember" class="rounded border-coffee/30 text-sanctuary focus:ring-sanctuary/40" />
                Se souvenir de moi
            </label>

            <button type="submit" :disabled="form.processing"
                class="w-full bg-sanctuary hover:bg-sanctuary-dark transition text-white rounded-lg py-2.5 font-medium disabled:opacity-60">
                Se connecter
            </button>
        </form>
    </div>
</template>
