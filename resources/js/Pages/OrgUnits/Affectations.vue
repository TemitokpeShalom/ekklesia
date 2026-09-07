<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

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
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white px-6 py-4 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ orgUnit.level_label }}</p>
                <h1 class="text-lg font-semibold">{{ orgUnit.name }}</h1>
            </div>
            <Link :href="`/org-units/${orgUnit.id}`" class="text-sm text-slate-500 hover:text-slate-900">Retour au tableau de bord</Link>
        </header>

        <main class="max-w-2xl mx-auto px-6 py-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Titulaires actuels</h2>
                <Link :href="`/org-units/${orgUnit.id}/inviter`" class="text-sm text-slate-900 font-medium hover:underline">Inviter un titulaire</Link>
            </div>

            <div v-if="affectations.length === 0" class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-400">
                Aucun titulaire actif pour ce noeud pour l'instant.
            </div>

            <ul v-else class="space-y-3">
                <li v-for="a in affectations" :key="a.id" class="bg-white border border-slate-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ a.user.name }}</p>
                            <p class="text-xs text-slate-400">{{ a.role.label }}</p>
                            <p v-if="a.user.phone || a.user.email" class="text-xs text-slate-400">{{ a.user.phone || a.user.email }}</p>
                        </div>
                        <button
                            v-if="revokingId !== a.id"
                            @click="startRevoke(a.id)"
                            class="text-sm text-red-600 hover:text-red-800 font-medium"
                        >
                            Révoquer
                        </button>
                    </div>

                    <div v-if="revokingId === a.id" class="mt-3 border-t border-slate-100 pt-3">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Motif (optionnel)</label>
                        <textarea v-model="reason" rows="2" class="w-full rounded-md border-slate-300 text-sm" placeholder="Décès, perte d'accès, remplacement..."></textarea>
                        <div class="flex items-center gap-3 mt-2">
                            <button
                                @click="confirmRevoke(a.id)"
                                class="rounded-lg bg-red-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-red-700"
                            >
                                Confirmer la révocation
                            </button>
                            <button @click="cancelRevoke" class="text-sm text-slate-500 hover:text-slate-900">Annuler</button>
                        </div>
                    </div>
                </li>
            </ul>

            <p class="text-xs text-slate-400 mt-6">
                Une affectation révoquée n'est jamais supprimée : elle reste dans l'historique. La personne conserve son compte et pourra recevoir une nouvelle affectation plus tard si besoin.
            </p>
        </main>
    </div>
</template>
