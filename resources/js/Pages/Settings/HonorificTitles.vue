<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
    titles: Array,
    defaults: Array,
})

const form = useForm({
    titles: [...props.titles],
})

function addTitle() {
    form.titles.push('')
}

function removeTitle(i) {
    form.titles.splice(i, 1)
}

function restoreDefaults() {
    form.titles = [...props.defaults]
}

function submit() {
    form.titles = form.titles.map((t) => t.trim()).filter((t) => t.length)
    form.put(`/org-units/${props.orgUnit.id}/titres-honorifiques`)
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Paramètres
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">Titres honorifiques</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">
                    Cette liste alimente le champ « titre » du formulaire membre, pour tout le ministère.
                </p>
            </div>
        </template>

        <div class="max-w-lg mx-auto">
            <form @submit.prevent="submit" class="glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <div class="space-y-2.5">
                    <div v-for="(t, i) in form.titles" :key="i" class="flex items-center gap-2">
                        <input v-model="form.titles[i]" type="text" maxlength="50"
                            class="flex-1 bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        <button type="button" @click="removeTitle(i)"
                            class="shrink-0 w-9 h-9 flex items-center justify-center rounded-xl text-graphite/62 hover:text-rose-600 hover:bg-rose-500/10 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <p v-if="form.errors.titles" class="mt-3 text-sm text-rose-600">{{ form.errors.titles }}</p>

                <div class="flex items-center gap-3 mt-5 pt-5 border-t border-graphite/10">
                    <button type="button" @click="addTitle"
                        class="text-sm font-medium text-sanctuary hover:text-gold transition">
                        + Ajouter un titre
                    </button>
                    <button type="button" @click="restoreDefaults"
                        class="ml-auto text-sm text-graphite/68 hover:text-graphite transition">
                        Rétablir les valeurs par défaut
                    </button>
                </div>

                <button type="submit" :disabled="form.processing"
                    class="w-full mt-6 inline-flex items-center justify-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-2.5 font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
                    Enregistrer
                </button>
            </form>
        </div>
    </AppLayout>
</template>
