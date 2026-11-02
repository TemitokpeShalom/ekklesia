<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Messagerie de l'equipe technique : a gauche toutes les discussions
 * (non lues en tete), a droite la discussion ouverte, avec zone de reponse.
 * Se rafraichit toute seule toutes les 15 secondes.
 */
const props = defineProps({ threads: Array, selected: Object })

const form = useForm({ message: '' })
const zone = ref(null)
let timer = null

function formatDate(iso) {
    if (!iso) return ''
    return new Date(iso).toLocaleString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

function descendre() {
    nextTick(() => { if (zone.value) zone.value.scrollTop = zone.value.scrollHeight })
}

function repondre() {
    if (!form.message.trim() || !props.selected) return
    form.post(`/technique/messagerie/${props.selected.id}`, {
        preserveScroll: true,
        onSuccess: () => { form.reset('message'); descendre() },
    })
}

function changerStatut(statut) {
    router.put(`/technique/messagerie/${props.selected.id}/statut`, { status: statut }, { preserveScroll: true })
}

function supprimer() {
    if (confirm('Supprimer définitivement cette discussion ?')) {
        router.delete(`/technique/messagerie/${props.selected.id}`)
    }
}

onMounted(() => {
    descendre()
    timer = setInterval(() => {
        if (!form.message.trim() && !form.processing) router.reload({ only: ['threads', 'selected'], preserveScroll: true })
    }, 15000)
})
onBeforeUnmount(() => clearInterval(timer))
watch(() => props.selected?.messages?.length, descendre)
</script>

<template>
    <AppLayout back-href="/technique" back-label="Espace technique">
        <template #title>
            <p class="text-[11px] uppercase tracking-wide text-sanctuary/80 font-semibold">Espace technique</p>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-graphite mt-1">Messagerie</h1>
        </template>

        <div class="grid md:grid-cols-[320px_1fr] gap-4">
            <aside class="rounded-2xl bg-white border border-graphite/10 shadow-card overflow-hidden">
                <p v-if="!threads.length" class="p-5 text-sm text-graphite/60">Aucun message pour l'instant. Quand quelqu'un écrira à l'équipe technique depuis la plateforme, sa discussion apparaîtra ici.</p>
                <Link v-for="t in threads" :key="t.id" :href="`/technique/messagerie?fil=${t.id}`" preserve-scroll
                    class="block px-4 py-3 border-b border-graphite/5 hover:bg-graphite/[0.03]"
                    :class="selected?.id === t.id ? 'bg-azure/5' : ''">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-sm text-graphite truncate flex-1">{{ t.user_name }}</span>
                        <span v-if="t.unread" class="w-2.5 h-2.5 rounded-full bg-sanctuary shrink-0"></span>
                        <span v-if="t.status === 'clos'" class="text-[10px] uppercase text-graphite/50 border border-graphite/15 rounded-full px-1.5">clos</span>
                    </div>
                    <p v-if="t.ministry_name" class="text-[11px] text-graphite/50 truncate">{{ t.ministry_name }}</p>
                    <p class="text-xs text-graphite/65 truncate mt-0.5">{{ t.last_body }}</p>
                    <p class="text-[11px] text-graphite/40 mt-0.5">{{ formatDate(t.last_message_at) }}</p>
                </Link>
            </aside>

            <section class="rounded-2xl bg-white border border-graphite/10 shadow-card flex flex-col min-h-[28rem]">
                <div v-if="!selected" class="m-auto text-center text-graphite/50 text-sm p-8">Choisissez une discussion à gauche pour la lire et répondre.</div>
                <template v-else>
                    <div class="flex items-center gap-3 px-4 py-3 border-b border-graphite/10">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-graphite truncate">{{ selected.user_name }}</p>
                            <p class="text-xs text-graphite/55 truncate">{{ selected.user_email }}<span v-if="selected.ministry_name"> · {{ selected.ministry_name }}</span></p>
                        </div>
                        <button type="button" class="text-xs text-azure hover:underline" @click="changerStatut(selected.status === 'clos' ? 'ouvert' : 'clos')">{{ selected.status === 'clos' ? 'Rouvrir' : 'Clôturer' }}</button>
                        <button type="button" class="text-xs text-rose-600 hover:underline" @click="supprimer">Supprimer</button>
                    </div>
                    <div ref="zone" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 max-h-[26rem]">
                        <div v-for="m in selected.messages" :key="m.id" class="flex" :class="m.from_staff ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[80%] rounded-2xl px-3.5 py-2.5 text-sm whitespace-pre-wrap break-words"
                                :class="m.from_staff ? 'bg-azure text-white rounded-br-sm' : 'bg-graphite/5 text-graphite rounded-bl-sm'">
                                {{ m.body }}
                                <span class="block text-[10px] mt-1 opacity-60">{{ formatDate(m.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                    <form class="p-3 border-t border-graphite/10 flex items-end gap-2" @submit.prevent="repondre">
                        <textarea v-model="form.message" rows="2" placeholder="Votre réponse…" class="flex-1 resize-none rounded-xl border border-graphite/15 px-3 py-2 text-sm"
                            @keydown.enter.exact.prevent="repondre"></textarea>
                        <button type="submit" :disabled="form.processing || !form.message.trim()" class="h-10 px-5 rounded-full bg-gradient-to-r from-azure to-azure-dark text-white text-sm font-semibold disabled:opacity-50">Envoyer</button>
                    </form>
                </template>
            </section>
        </div>
    </AppLayout>
</template>
