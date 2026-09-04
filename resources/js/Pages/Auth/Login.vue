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
    <div class="min-h-screen flex items-center justify-center px-4">
        <form @submit.prevent="submit" class="w-full max-w-sm bg-white p-8 rounded-lg shadow-sm border border-slate-200">
            <h1 class="text-xl font-semibold mb-1">Ekklesia</h1>
            <p class="text-sm text-slate-500 mb-6">Connectez-vous à votre espace.</p>

            <label class="block text-sm font-medium mb-1" for="email">Adresse e-mail</label>
            <input id="email" v-model="form.email" type="email" required autofocus
                class="w-full border border-slate-300 rounded px-3 py-2 mb-1" />
            <p v-if="form.errors.email" class="text-sm text-red-600 mb-3">{{ form.errors.email }}</p>

            <label class="block text-sm font-medium mb-1 mt-3" for="password">Mot de passe</label>
            <input id="password" v-model="form.password" type="password" required
                class="w-full border border-slate-300 rounded px-3 py-2 mb-1" />
            <p v-if="form.errors.password" class="text-sm text-red-600 mb-3">{{ form.errors.password }}</p>

            <label class="flex items-center gap-2 text-sm my-4">
                <input type="checkbox" v-model="form.remember" />
                Se souvenir de moi
            </label>

            <button type="submit" :disabled="form.processing"
                class="w-full bg-slate-900 text-white rounded py-2 font-medium disabled:opacity-60">
                Se connecter
            </button>
        </form>
    </div>
</template>
