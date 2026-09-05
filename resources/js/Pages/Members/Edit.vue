<script setup>
import { useForm, Link, router } from '@inertiajs/vue3'

const props = defineProps({
    orgUnit: Object,
    member: Object,
})

const form = useForm({
    first_name: props.member.first_name,
    last_name: props.member.last_name,
    phone: props.member.phone,
    email: props.member.email,
    gender: props.member.gender,
    birth_date: props.member.birth_date,
    joined_at: props.member.joined_at,
    status: props.member.status,
})

function submit() {
    form.put(`/org-units/${props.orgUnit.id}/membres/${props.member.id}`)
}

function destroy() {
    if (confirm('Retirer définitivement ce membre ?')) {
        router.delete(`/org-units/${props.orgUnit.id}/membres/${props.member.id}`)
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white px-6 py-4 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ orgUnit.level_label }}</p>
                <h1 class="text-lg font-semibold">{{ orgUnit.name }}</h1>
            </div>
            <Link :href="`/org-units/${orgUnit.id}/membres`" class="text-sm text-slate-500 hover:text-slate-900">Annuler</Link>
        </header>

        <main class="max-w-lg mx-auto px-6 py-8">
            <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-6">
                Modifier {{ member.first_name }} {{ member.last_name }}
            </h2>

            <form @submit.prevent="submit" class="space-y-4 bg-white border border-slate-200 rounded-lg p-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Prénom</label>
                        <input v-model="form.first_name" type="text" class="w-full rounded-md border-slate-300 text-sm" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nom</label>
                        <input v-model="form.last_name" type="text" class="w-full rounded-md border-slate-300 text-sm" required />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Téléphone</label>
                    <input v-model="form.phone" type="text" class="w-full rounded-md border-slate-300 text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input v-model="form.email" type="email" class="w-full rounded-md border-slate-300 text-sm" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Genre</label>
                        <select v-model="form.gender" class="w-full rounded-md border-slate-300 text-sm">
                            <option value="">Non précisé</option>
                            <option value="M">Homme</option>
                            <option value="F">Femme</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Date de naissance</label>
                        <input v-model="form.birth_date" type="date" class="w-full rounded-md border-slate-300 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Date d'adhésion</label>
                    <input v-model="form.joined_at" type="date" class="w-full rounded-md border-slate-300 text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Statut</label>
                    <select v-model="form.status" class="w-full rounded-md border-slate-300 text-sm">
                        <option value="active">Actif</option>
                        <option value="inactive">Inactif</option>
                    </select>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50"
                    >
                        Enregistrer
                    </button>
                    <button
                        type="button"
                        @click="destroy"
                        class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                    >
                        Retirer
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
