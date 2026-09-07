<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    orgUnit: Object,
    candidateParents: Array,
    history: Array,
})

const page = usePage()
const success = computed(() => page.props.flash?.success)
const error = computed(() => page.props.flash?.error)

const TRANSFORMATION_LABELS = {
    creation: 'Création',
    promotion: 'Changement de niveau',
    rattachement: 'Rattachement',
    renommage: 'Renommage',
    scission: 'Scission',
    fusion: 'Fusion',
    fermeture: 'Fermeture',
}

const form = useForm({
    transformation_type: 'renommage',
    reason: '',
    name: props.orgUnit.name,
    new_parent_id: '',
})

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/transformation`, {
        preserveScroll: true,
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

        <main class="max-w-2xl mx-auto px-6 py-8">
            <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-6">Transformer cette entité</h2>

            <div v-if="success" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-sm font-medium text-emerald-800">{{ success }}</p>
            </div>
            <div v-if="error" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-medium text-red-800">{{ error }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4 bg-white border border-slate-200 rounded-lg p-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Type de transformation</label>
                    <select v-model="form.transformation_type" class="w-full rounded-md border-slate-300 text-sm">
                        <option value="renommage">Renommer</option>
                        <option value="promotion">Changer de niveau (promotion)</option>
                        <option value="rattachement">Rattacher à une autre entité</option>
                    </select>
                </div>

                <div v-if="form.transformation_type === 'renommage'">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nouveau nom</label>
                    <input v-model="form.name" type="text" class="w-full rounded-md border-slate-300 text-sm" required />
                    <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                </div>

                <p v-if="form.transformation_type === 'promotion'" class="text-xs text-slate-500">
                    L'entité passera automatiquement au niveau immédiatement supérieur (par exemple Cellule → Église locale). Ce n'est possible que si un niveau supérieur existe pour le rang actuel.
                </p>

                <div v-if="form.transformation_type === 'rattachement'">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nouvelle entité parente</label>
                    <select v-model="form.new_parent_id" class="w-full rounded-md border-slate-300 text-sm" required>
                        <option value="" disabled>Choisir une entité</option>
                        <option v-for="candidate in candidateParents" :key="candidate.id" :value="candidate.id">
                            {{ candidate.name }} ({{ candidate.level_label }})
                        </option>
                    </select>
                    <p v-if="form.errors.new_parent_id" class="text-xs text-red-600 mt-1">{{ form.errors.new_parent_id }}</p>
                    <p class="mt-1 text-xs text-slate-400">
                        Seules les entités du même ministère apparaissent ici, à un rang strictement supérieur. Pour rejoindre un autre ministère, utilisez un code de rattachement.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Motif</label>
                    <textarea v-model="form.reason" rows="2" class="w-full rounded-md border-slate-300 text-sm" required></textarea>
                    <p v-if="form.errors.reason" class="text-xs text-red-600 mt-1">{{ form.errors.reason }}</p>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50"
                    >
                        Appliquer la transformation
                    </button>
                </div>
            </form>

            <p class="text-xs text-slate-400 mt-4">
                Scission, fusion et fermeture d'entités ne sont pas encore disponibles : ces opérations touchent souvent plusieurs entités à la fois (membres, finances, affectations) et demandent une décision explicite sur la répartition ou la combinaison des données avant d'être automatisées.
            </p>

            <section class="mt-10">
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Historique</h2>
                <div v-if="history.length === 0" class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-400">
                    Aucune transformation enregistrée pour l'instant.
                </div>
                <ul v-else class="space-y-2">
                    <li v-for="entry in history" :key="entry.id" class="bg-white border border-slate-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-900">{{ TRANSFORMATION_LABELS[entry.transformation_type] || entry.transformation_type }}</span>
                            <span class="text-xs text-slate-400">
                                {{ entry.valid_from }}<span v-if="entry.valid_to"> → {{ entry.valid_to }}</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ entry.name }} · {{ entry.level_label }}</p>
                        <p v-if="entry.reason" class="text-xs text-slate-400 mt-1">{{ entry.reason }}</p>
                    </li>
                </ul>
            </section>
        </main>
    </div>
</template>
