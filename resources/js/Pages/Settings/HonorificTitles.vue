<script setup>
import { useForm, Link } from '@inertiajs/vue3'

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
    <div class="min-h-screen bg-parchment">
        <header class="border-b border-coffee/10 bg-white px-6 py-5 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-widest text-gold-dark font-semibold">{{ orgUnit.level_label }}</p>
                <h1 class="font-serif text-xl text-ink">{{ orgUnit.name }}</h1>
            </div>
            <Link :href="`/org-units/${orgUnit.id}`" class="text-sm text-coffee-light hover:text-ink transition">Retour au tableau de bord</Link>
        </header>

        <main class="max-w-lg mx-auto px-6 py-10">
            <h2 class="font-serif text-2xl text-ink mb-1">Titres honorifiques</h2>
            <p class="text-sm text-coffee-light mb-8">
                Cette liste alimente le champ « titre » du formulaire membre, pour tout le ministère.
            </p>

            <form @submit.prevent="submit" class="bg-white border border-coffee/10 rounded-3xl shadow-sm p-6">
                <div class="space-y-2.5">
                    <div v-for="(t, i) in form.titles" :key="i" class="flex items-center gap-2">
                        <input v-model="form.titles[i]" type="text" maxlength="50"
                            class="flex-1 border border-coffee/20 rounded-xl px-3.5 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-sanctuary/40 focus:border-sanctuary" />
                        <button type="button" @click="removeTitle(i)"
                            class="shrink-0 w-9 h-9 flex items-center justify-center rounded-xl text-coffee-light hover:text-sanctuary hover:bg-sanctuary/5 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <p v-if="form.errors.titles" class="text-sm text-red-600 mt-3">{{ form.errors.titles }}</p>

                <div class="flex items-center gap-3 mt-5 pt-5 border-t border-coffee/10">
                    <button type="button" @click="addTitle"
                        class="text-sm font-medium text-sanctuary hover:text-sanctuary-dark transition">
                        + Ajouter un titre
                    </button>
                    <button type="button" @click="restoreDefaults"
                        class="text-sm text-coffee-light hover:text-ink transition ml-auto">
                        Rétablir les valeurs par défaut
                    </button>
                </div>

                <button type="submit" :disabled="form.processing"
                    class="w-full mt-6 bg-gradient-to-r from-sanctuary to-sanctuary-dark hover:from-sanctuary-dark hover:to-sanctuary-dark transition-all duration-300 text-white rounded-xl py-2.5 font-medium shadow-lg shadow-sanctuary/30 disabled:opacity-60">
                    Enregistrer
                </button>
            </form>
        </main>
    </div>
</template>
