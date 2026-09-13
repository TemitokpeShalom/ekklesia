<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Signalements techniques (2026-09-13) : remontees faites par les
 * ministeres depuis le widget assistant (bouton "signaler un problème à
 * l'équipe technique" dans sa fenêtre) - voir AssistantController::
 * flagFeedback et le modele AssistantPlatformFeedback. Jusqu'ici visibles
 * UNIQUEMENT via la commande serveur `php artisan assistant:feedback`
 * (retour du ministere, 13/09 : "on me disait que pour voir ça, il devait
 * taper un code sur le serveur d'abord") - cet ecran les rend enfin
 * consultables et traitables ici, sans quitter la plateforme.
 */
defineProps({
    signalements: Array,
})

const page = usePage()
const flashSuccess = computed(() => page.props.flash?.success)

const form = useForm({ status: '' })

function statusLabel(status) {
    if (status === 'lu') return 'Lu'
    if (status === 'traite') return 'Traité'
    return 'Nouveau'
}

function statusClass(status) {
    if (status === 'traite') return 'bg-forest/10 text-forest'
    if (status === 'lu') return 'bg-graphite/10 text-graphite/70'
    return 'bg-sanctuary/10 text-sanctuary'
}

function updateStatus(s, status) {
    form.status = status
    form.put(`/technique/signalements/${s.id}`, { preserveScroll: true })
}

function formatDate(iso) {
    return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
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
                <h1 class="font-serif text-4xl font-bold text-graphite">Signalements techniques</h1>
                <Link href="/technique/equipe" class="text-sm text-azure hover:underline font-medium">Équipe technique</Link>
            </div>
        </template>

        <p v-if="flashSuccess" class="text-sm text-forest bg-forest/10 border border-forest/30 rounded-xl px-4 py-2.5 mb-6">{{ flashSuccess }}</p>

        <div class="rounded-2xl border border-graphite/10 divide-y divide-graphite/10">
            <div v-for="s in signalements" :key="s.id" class="px-5 py-4 flex items-start justify-between gap-4 flex-wrap">
                <div class="flex-1 min-w-[240px]">
                    <div class="flex items-center gap-2 flex-wrap mb-1.5">
                        <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(s.status)">{{ statusLabel(s.status) }}</span>
                        <span v-if="s.ministry_name" class="text-graphite/50 text-xs">{{ s.ministry_name }}</span>
                        <span v-if="s.user_name" class="text-graphite/50 text-xs">· {{ s.user_name }}<span v-if="s.user_email"> ({{ s.user_email }})</span></span>
                    </div>
                    <p class="text-graphite">{{ s.message }}</p>
                    <p class="text-graphite/40 text-xs mt-1.5">{{ formatDate(s.created_at) }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button v-if="s.status !== 'lu'" type="button" @click="updateStatus(s, 'lu')" class="text-sm text-graphite/60 hover:underline">Marquer lu</button>
                    <button v-if="s.status !== 'traite'" type="button" @click="updateStatus(s, 'traite')" class="text-sm text-forest hover:underline">Marquer traité</button>
                </div>
            </div>
            <p v-if="!signalements.length" class="px-5 py-6 text-graphite/60 text-sm">Aucun signalement pour l'instant.</p>
        </div>
    </AppLayout>
</template>
