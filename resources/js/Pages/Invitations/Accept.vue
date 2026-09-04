<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ token: String });

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
    <div class="min-h-screen flex items-center justify-center px-4">
        <form @submit.prevent="submit" class="w-full max-w-sm bg-white p-8 rounded-lg shadow-sm border border-slate-200">
            <h1 class="text-xl font-semibold mb-1">Créer votre compte</h1>
            <p class="text-sm text-slate-500 mb-6">
                Ce compte est personnel — il vous appartient, même si votre poste change plus tard.
            </p>

            <label class="block text-sm font-medium mb-1">Nom complet</label>
            <input v-model="form.name" required class="w-full border border-slate-300 rounded px-3 py-2 mb-3" />

            <label class="block text-sm font-medium mb-1">Adresse e-mail</label>
            <input v-model="form.email" type="email" required class="w-full border border-slate-300 rounded px-3 py-2 mb-3" />

            <label class="block text-sm font-medium mb-1">Téléphone (optionnel)</label>
            <input v-model="form.phone" class="w-full border border-slate-300 rounded px-3 py-2 mb-3" />

            <label class="block text-sm font-medium mb-1">Mot de passe</label>
            <input v-model="form.password" type="password" required class="w-full border border-slate-300 rounded px-3 py-2 mb-3" />

            <label class="block text-sm font-medium mb-1">Confirmer le mot de passe</label>
            <input v-model="form.password_confirmation" type="password" required class="w-full border border-slate-300 rounded px-3 py-2 mb-4" />

            <button type="submit" :disabled="form.processing"
                class="w-full bg-slate-900 text-white rounded py-2 font-medium disabled:opacity-60">
                Créer mon compte
            </button>
        </form>
    </div>
</template>
