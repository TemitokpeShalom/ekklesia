<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
    candidateParents: Array,
    history: Array,
})

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
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Transformation
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">Transformer cette entité</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto space-y-8">
            <!-- Succes/erreur : bandeau desormais commun a tout AppLayout (voir AppLayout.vue), plus besoin de le dupliquer ici. -->

            <form @submit.prevent="submit" class="space-y-4 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <div>
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Type de transformation</label>
                    <select v-model="form.transformation_type" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                        <option value="renommage" class="bg-white text-graphite">Renommer</option>
                        <option value="promotion" class="bg-white text-graphite">Changer de niveau (promotion)</option>
                        <option value="rattachement" class="bg-white text-graphite">Rattacher à une autre entité</option>
                    </select>
                </div>

                <div v-if="form.transformation_type === 'renommage'">
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Nouveau nom</label>
                    <input v-model="form.name" type="text" required class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p>
                </div>

                <p v-if="form.transformation_type === 'promotion'" class="text-xs text-graphite/65">
                    L'entité passera automatiquement au niveau immédiatement supérieur (par exemple Cellule → Église locale). Ce n'est possible que si un niveau supérieur existe pour le rang actuel.
                </p>

                <div v-if="form.transformation_type === 'rattachement'">
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Nouvelle entité parente</label>
                    <select v-model="form.new_parent_id" required class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                        <option value="" disabled class="bg-white text-graphite">Choisir une entité</option>
                        <option v-for="candidate in candidateParents" :key="candidate.id" :value="candidate.id" class="bg-white text-graphite">
                            {{ candidate.name }} ({{ candidate.level_label }})
                        </option>
                    </select>
                    <p v-if="form.errors.new_parent_id" class="mt-1 text-sm text-rose-600">{{ form.errors.new_parent_id }}</p>
                    <p class="mt-1 text-xs text-graphite/58">
                        Seules les entités du même ministère apparaissent ici, à un rang strictement supérieur. Pour rejoindre un autre ministère, utilisez un code de rattachement.
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Motif</label>
                    <textarea v-model="form.reason" rows="2" required class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                    <p v-if="form.errors.reason" class="mt-1 text-sm text-rose-600">{{ form.errors.reason }}</p>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60"
                    >
                        Appliquer la transformation
                    </button>
                </div>
            </form>

            <p class="text-xs text-graphite/58">
                Scission, fusion et fermeture d'entités ne sont pas encore disponibles : ces opérations touchent souvent plusieurs entités à la fois (membres, finances, affectations) et demandent une décision explicite sur la répartition ou la combinaison des données avant d'être automatisées.
            </p>

            <section>
                <h2 class="mb-3 text-xs font-semibold text-sanctuary/80 uppercase tracking-widest">Historique</h2>
                <div v-if="history.length === 0" class="glass-panel rounded-2xl px-4 py-6 text-center text-sm text-graphite/62">
                    Aucune transformation enregistrée pour l'instant.
                </div>
                <ul v-else class="space-y-2">
                    <li v-for="entry in history" :key="entry.id" class="glass-panel rounded-2xl p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-graphite">{{ TRANSFORMATION_LABELS[entry.transformation_type] || entry.transformation_type }}</span>
                            <span class="text-xs text-graphite/62">
                                {{ entry.valid_from }}<span v-if="entry.valid_to"> → {{ entry.valid_to }}</span>
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-graphite/65">{{ entry.name }} · {{ entry.level_label }}</p>
                        <p v-if="entry.reason" class="mt-1 text-xs text-graphite/58">{{ entry.reason }}</p>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
