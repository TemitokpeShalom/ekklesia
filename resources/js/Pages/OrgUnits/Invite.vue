<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    orgUnit: Object,
    roles: Array,
})

const page = usePage()
const invitationLink = computed(() => page.props.flash?.invitation_link)

const form = useForm({
    role_id: '',
    email: '',
})

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/inviter`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
        },
    })
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white px-6 py-4 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ orgUnit.level_label }}</p>
                <h1 class="text-lg font-semibold">{{ orgUnit.name }}</h1>
            </div>
            <Link :href="`/org-units/${orgUnit.id}`" class="text-sm text-slate-500 hover:text-slate-900">Retour au tableau de bord</Link>
        </header>

        <main class="max-w-lg mx-auto px-6 py-8">
            <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-6">Inviter un titulaire</h2>

            <div v-if="invitationLink" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-sm font-medium text-emerald-800 mb-2">Invitation créée. Partagez ce lien à la personne concernée (WhatsApp, SMS...) :</p>
                <p class="text-sm break-all bg-white border border-emerald-200 rounded px-3 py-2 text-emerald-900">{{ invitationLink }}</p>
                <p class="text-xs text-emerald-700 mt-2">Ce lien est valable 7 jours et ne peut être utilisé qu'une seule fois.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4 bg-white border border-slate-200 rounded-lg p-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Poste à pourvoir</label>
                    <select v-model="form.role_id" class="w-full rounded-md border-slate-300 text-sm" required>
                        <option value="" disabled>Choisir un poste</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.label }}</option>
                    </select>
                    <p v-if="form.errors.role_id" class="text-xs text-red-600 mt-1">{{ form.errors.role_id }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email (optionnel)</label>
                    <input v-model="form.email" type="email" class="w-full rounded-md border-slate-300 text-sm" placeholder="pour référence, le lien reste à partager manuellement" />
                    <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50"
                    >
                        Générer le lien d'invitation
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
