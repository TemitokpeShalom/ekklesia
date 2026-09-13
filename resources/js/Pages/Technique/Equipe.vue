<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Gestion de l'equipe technique Oikonema (2026-09-13, premiere livraison) -
 * voir TechniqueController::equipe. Ajouter quelqu'un ici suppose qu'il ou
 * elle possede deja un compte Oikonema (peu importe le ministere) : cette
 * appartenance est independante de tout ministere, voir la migration de
 * creation de technical_staff.
 */
defineProps({
    membres: Array,
    amorcesParEmail: Array,
})

const page = usePage()
const flashSuccess = computed(() => page.props.flash?.success)
const flashError = computed(() => page.props.flash?.error)

const form = useForm({ email: '' })

function submit() {
    form.post('/technique/equipe', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}

function retirer(membre) {
    if (!confirm(`Retirer ${membre.name} de l'équipe technique ?`)) return
    form.delete(`/technique/equipe/${membre.id}`, { preserveScroll: true })
}

function formatDate(iso) {
    return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}
</script>

<template>
    <AppLayout back-href="/technique" back-label="Retour à l'espace technique">
        <template #title>
            <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2 mb-3">
                <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                Espace technique
            </p>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <h1 class="font-serif text-4xl font-bold text-graphite">Équipe technique</h1>
                <Link href="/technique/signalements" class="text-sm text-azure hover:underline font-medium">Signalements techniques</Link>
            </div>
        </template>

        <p v-if="flashSuccess" class="text-sm text-forest bg-forest/10 border border-forest/30 rounded-xl px-4 py-2.5 mb-6">{{ flashSuccess }}</p>
        <p v-if="flashError" class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5 mb-6">{{ flashError }}</p>

        <form @submit.prevent="submit" class="glass-panel-light rounded-2xl p-5 mb-8 flex flex-wrap items-end gap-3">
            <label class="flex-1 min-w-[240px]">
                <span class="block text-sm text-graphite/70 mb-1.5">Adresse e-mail d'un compte Oikonema existant</span>
                <input v-model="form.email" type="email" required placeholder="nom@exemple.com"
                    class="w-full rounded-xl border border-graphite/15 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-azure/40" />
                <span v-if="form.errors.email" class="block text-rose-600 text-xs mt-1">{{ form.errors.email }}</span>
            </label>
            <button type="submit" :disabled="form.processing"
                class="rounded-full bg-gradient-to-r from-azure to-azure-dark text-white px-6 py-2.5 text-sm font-medium shadow-azure/20 shadow-lg disabled:opacity-60">
                Ajouter à l'équipe
            </button>
        </form>

        <div class="rounded-2xl border border-graphite/10 divide-y divide-graphite/10">
            <div v-for="m in membres" :key="m.id" class="px-5 py-4 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-graphite font-medium">{{ m.name }}</p>
                    <p class="text-graphite/55 text-sm">{{ m.email }}</p>
                    <p class="text-graphite/40 text-xs mt-0.5">Ajouté le {{ formatDate(m.created_at) }}<span v-if="m.added_by"> par {{ m.added_by }}</span></p>
                </div>
                <button @click="retirer(m)" type="button" class="text-sm text-sanctuary hover:underline">Retirer</button>
            </div>
            <p v-if="!membres.length && !amorcesParEmail.length" class="px-5 py-6 text-graphite/60 text-sm">Personne pour l'instant.</p>
        </div>

        <div v-if="amorcesParEmail.length" class="mt-6 text-sm text-graphite/55">
            <p class="mb-1">Ces adresses ont accès mais n'ont pas encore de compte (réglées directement sur le serveur) :</p>
            <p>{{ amorcesParEmail.join(', ') }}</p>
            <p class="mt-1">Chacune peut créer son propre compte sur
                <a href="/equipe-technique/creer-mon-compte" class="text-azure hover:underline font-medium">/equipe-technique/creer-mon-compte</a>.
            </p>
        </div>
    </AppLayout>
</template>
