<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
    affectations: Array,
})

const revokingId = ref(null)
const reason = ref('')

function startRevoke(affectationId) {
    revokingId.value = affectationId
    reason.value = ''
}

function cancelRevoke() {
    revokingId.value = null
    reason.value = ''
}

function confirmRevoke(affectationId) {
    router.delete(`/org-units/${props.orgUnit.id}/acces/${affectationId}`, {
        data: { revocation_reason: reason.value },
        preserveScroll: true,
        onFinish: () => {
            revokingId.value = null
            reason.value = ''
        },
    })
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Accès et postes
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">Titulaires actuels</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto space-y-8">
            <div class="flex justify-end animate-[fadeInUp_0.5s_ease-out_both]">
                <Link
                    :href="`/org-units/${orgUnit.id}/inviter`"
                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20"
                >
                    Inviter un titulaire
                </Link>
            </div>

            <div v-if="affectations.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center animate-[fadeInUp_0.55s_ease-out_both]">
                <p class="text-sm font-medium text-graphite/80">Aucun titulaire actif pour ce noeud pour l'instant.</p>
            </div>

            <ul v-else class="space-y-3">
                <li
                    v-for="(a, i) in affectations"
                    :key="a.id"
                    class="glass-panel rounded-2xl p-5 animate-[fadeInUp_0.5s_ease-out_both]"
                    :style="{ animationDelay: `${Math.min(i, 10) * 40}ms` }"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-graphite">{{ a.user.name }}</p>
                            <p class="text-xs text-graphite/62">{{ a.role.label }}</p>
                            <p v-if="a.user.phone || a.user.email" class="text-xs text-graphite/62">{{ a.user.phone || a.user.email }}</p>
                        </div>
                        <button
                            v-if="revokingId !== a.id"
                            @click="startRevoke(a.id)"
                            class="text-sm font-medium text-rose-600 hover:text-rose-500"
                        >
                            Révoquer
                        </button>
                    </div>

                    <div v-if="revokingId === a.id" class="mt-3 border-t border-graphite/10 pt-3">
                        <label class="mb-1 block text-xs font-medium text-graphite/80">Motif (optionnel)</label>
                        <textarea v-model="reason" rows="2" placeholder="Décès, perte d'accès, remplacement..." class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                        <div class="mt-2 flex items-center gap-3">
                            <button
                                @click="confirmRevoke(a.id)"
                                class="rounded-xl bg-rose-500/90 px-4 py-1.5 text-sm font-medium text-graphite hover:bg-rose-500"
                            >
                                Confirmer la révocation
                            </button>
                            <button @click="cancelRevoke" class="text-sm text-graphite/68 hover:text-graphite">Annuler</button>
                        </div>
                    </div>
                </li>
            </ul>

            <p class="text-xs text-graphite/58">
                Une affectation révoquée n'est jamais supprimée : elle reste dans l'historique. La personne conserve son compte et pourra recevoir une nouvelle affectation plus tard si besoin.
            </p>
        </div>
    </AppLayout>
</template>
