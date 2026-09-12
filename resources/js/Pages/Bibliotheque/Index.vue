<script setup>
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 *
 * Corrige le 2026-09-12 (retour du ministere) : accessible a tout niveau
 * desormais (voir BibliothequeController), pas seulement au Ministere -
 * "Retour" (deja fourni par back-href ci-dessous) ramene donc au tableau
 * de bord du niveau consulte, pas forcement a la racine.
 *
 * Corrige le 2026-09-12 (retour du ministere, deuxieme passe) : "les
 * messages ne respectent pas l'ordre hierarchique... a l'interieur du
 * ministere" - la liste montre desormais TOUS les messages du ministere,
 * pas seulement ceux de l'entite consultee et ses descendants. Chaque
 * message affiche donc aussi son eglise/entite d'origine ET l'unite juste
 * au-dessus (ex. "Cellule Bethel · District Nord"), pour rester reperable
 * une fois les resultats etales sur tout le ministere.
 */
const props = defineProps({
    orgUnit: Object,
    messages: Array,
    search: String,
})

const query = ref(props.search)

function submitSearch() {
    router.get(`/org-units/${props.orgUnit.id}/bibliotheque`, { q: query.value }, { preserveState: true })
}

function formatDate(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Bibliothèque
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">Bibliothèque ministérielle</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">Messages prêchés dans tout le ministère, accès réservé à ceux qui prêchent.</p>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-8">
            <form @submit.prevent="submitSearch" class="flex gap-2 animate-[fadeInUp_0.55s_ease-out_both]">
                <input
                    v-model="query"
                    type="text"
                    placeholder="Rechercher un thème, un orateur, un verset..."
                    class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"
                />
                <button
                    type="submit"
                    class="flex-shrink-0 inline-flex items-center bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20"
                >
                    Rechercher
                </button>
            </form>

            <div v-if="messages.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center animate-[fadeInUp_0.6s_ease-out_both]">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-graphite/5 text-graphite/62">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </span>
                <p class="mt-4 text-sm font-medium text-graphite/80">Aucun message trouvé.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(message, i) in messages"
                    :key="message.id"
                    class="glass-panel rounded-2xl p-5 hover:border-graphite/20 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                    :style="{ animationDelay: `${Math.min(i, 10) * 40}ms` }"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-semibold text-graphite text-[15px]">{{ message.title }}</p>
                        <span class="flex-shrink-0 text-xs text-graphite/62">{{ formatDate(message.service_date) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-graphite/62">
                        {{ message.org_unit?.name }}
                        <span v-if="message.org_unit?.parent"> ({{ message.org_unit.parent.name }})</span>
                        <span v-if="message.speaker"> · {{ message.speaker }}</span>
                    </p>
                    <p v-if="message.key_verses" class="mt-3 text-sm text-graphite/80">
                        <span class="font-medium text-graphite/90">Versets :</span> {{ message.key_verses }}
                    </p>
                    <p v-if="message.notes" class="mt-2 whitespace-pre-line text-sm text-graphite/80">{{ message.notes }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
