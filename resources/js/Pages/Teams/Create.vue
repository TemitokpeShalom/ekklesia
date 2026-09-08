<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Equipes et benevolat (point 08), ecran construit directement dans le
 * style v3 "Vitrail".
 */
const props = defineProps({
    orgUnit: Object,
})

const form = useForm({
    name: '',
    description: '',
})

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/equipes`)
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/equipes`" back-label="Annuler">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Équipes et bénévolat
                </p>
                <h1 class="font-serif text-3xl text-white mt-2">Créer une équipe</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <section>
                    <h2 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest mb-4">Nom de l'équipe</h2>
                    <input v-model="form.name" type="text" placeholder="Accueil, Louange, Enfants, Technique..." class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-rose-400">{{ form.errors.name }}</p>
                </section>

                <section class="border-t border-white/10 pt-6">
                    <h2 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest mb-4">Description (optionnel)</h2>
                    <textarea v-model="form.description" rows="3" class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                </section>

                <div class="border-t border-white/10 pt-6">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Créer
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
