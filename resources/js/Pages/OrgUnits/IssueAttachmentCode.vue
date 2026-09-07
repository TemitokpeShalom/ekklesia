<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    orgUnit: Object,
})

const page = usePage()
const plainCode = computed(() => page.props.flash?.plain_code)

const LEVEL_NAMES = ['Ministère', 'Continent', 'Pays', 'Région', 'District', 'Église locale', 'Cellule']

const availableRanks = computed(() =>
    LEVEL_NAMES
        .map((label, rank) => ({ rank, label }))
        .filter(({ rank }) => rank > props.orgUnit.level_rank)
)

const form = useForm({
    target_level_rank: '',
    valid_for_hours: 72,
})

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/code-de-rattachement`, {
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

        <main class="max-w-lg mx-auto px-6 py-8">
            <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-6">Émettre un code de rattachement</h2>

            <div v-if="plainCode" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-sm font-medium text-emerald-800 mb-2">Code généré. Transmettez-le à la personne qui va créer la nouvelle entité :</p>
                <p class="text-2xl font-mono tracking-widest bg-white border border-emerald-200 rounded px-3 py-2 text-emerald-900 text-center">{{ plainCode }}</p>
                <p class="text-xs text-emerald-700 mt-2">Ce code ne peut être utilisé qu'une seule fois et expire après la durée choisie.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4 bg-white border border-slate-200 rounded-lg p-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Niveau de la nouvelle entité</label>
                    <select v-model="form.target_level_rank" class="w-full rounded-md border-slate-300 text-sm" required>
                        <option value="" disabled>Choisir un niveau</option>
                        <option v-for="opt in availableRanks" :key="opt.rank" :value="opt.rank">{{ opt.label }}</option>
                    </select>
                    <p v-if="form.errors.target_level_rank" class="text-xs text-red-600 mt-1">{{ form.errors.target_level_rank }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Durée de validité (heures)</label>
                    <input v-model.number="form.valid_for_hours" type="number" min="1" max="720" class="w-full rounded-md border-slate-300 text-sm" />
                    <p class="mt-1 text-xs text-slate-400">72 heures par défaut, 720 heures (30 jours) maximum.</p>
                    <p v-if="form.errors.valid_for_hours" class="text-xs text-red-600 mt-1">{{ form.errors.valid_for_hours }}</p>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50"
                    >
                        Générer le code
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
