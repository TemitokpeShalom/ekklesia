<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { assistantFetch } from '../../assistantApi'

/**
 * Assistant IA integre a Oikonema (chantier du 2026-09-10, "autres
 * corrections" point 1). Monte UNE SEULE FOIS, globalement, depuis
 * app.js (pas depuis AppLayout.vue) : plusieurs ecrans plus anciens
 * n'utilisent pas encore ce layout (voir son commentaire d'en-tete), et
 * l'assistant doit rester visible partout des la connexion, pas
 * seulement sur les ecrans deja migres vers l'identite "Vitrail".
 *
 * N'affiche rien tant qu'aucun utilisateur n'est connecte (pages
 * publiques : /bienvenue, /connexion, /rattachement, /invitations/...,
 * /ministeres/nouveau) - usePage().props.auth.user est partage
 * globalement par HandleInertiaRequests, donc toujours a jour meme si ce
 * composant n'est jamais demonte entre deux visites Inertia.
 */
const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const currentOrgUnitId = computed(() => page.props.orgUnit?.id ?? null)

const open = ref(false)
// 'choix' : deux grands choix ; 'david' : assistant en ligne ; 'equipe' : message a l'equipe technique
const mode = ref('choix')
const equipeMessages = ref([])
const equipeDraft = ref('')
const equipeSending = ref(false)
const equipeError = ref(null)
const equipeScroll = ref(null)
const nonLus = ref(0)
let equipeTimer = null
const loaded = ref(false)
const loading = ref(false)
const sending = ref(false)
const errorMessage = ref(null)
const dailyLimitReached = ref(false)

const assistantName = ref('Assistant Oikonema')
const assistantTagline = ref('')
const conversationId = ref(null)
const messages = ref([])

const draft = ref('')
const pendingFiles = ref([])
const fileInput = ref(null)
const scrollArea = ref(null)

const flagging = ref(false)
const flagText = ref('')
const flagSent = ref(false)

const initials = computed(() => {
    const parts = assistantName.value.trim().split(/\s+/).filter(Boolean)
    return parts.slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('') || 'IA'
})

async function ensureLoaded() {
    if (loaded.value || loading.value) return
    loading.value = true
    errorMessage.value = null

    try {
        const data = await fetchConversation('/assistant/conversation')
        applyConversation(data)
        loaded.value = true
    } catch (e) {
        errorMessage.value = "Impossible de joindre l'assistant pour le moment."
    } finally {
        loading.value = false
    }
}

function fetchConversation(url) {
    const qs = currentOrgUnitId.value ? `?org_unit_id=${encodeURIComponent(currentOrgUnitId.value)}` : ''
    return assistantFetch(url + qs)
}

function applyConversation(data) {
    assistantName.value = data.assistant?.name ?? assistantName.value
    assistantTagline.value = data.assistant?.tagline ?? ''
    conversationId.value = data.conversation?.id ?? null
    messages.value = data.messages ?? []
    dailyLimitReached.value = !!data.dailyLimitReached
    scrollToBottom()
}

function toggleOpen() {
    open.value = !open.value
    if (open.value) {
        mode.value = 'choix'
    }
}

function choisir(m) {
    mode.value = m
    if (m === 'david') {
        ensureLoaded()
        nextTick(scrollToBottom)
    } else if (m === 'equipe') {
        chargerEquipe()
    }
}

function retourChoix() {
    mode.value = 'choix'
    flagging.value = false
}

function equipeDescendre() {
    nextTick(() => { if (equipeScroll.value) equipeScroll.value.scrollTop = equipeScroll.value.scrollHeight })
}

async function chargerEquipe(silencieux = false) {
    try {
        const data = await assistantFetch('/assistance/equipe')
        equipeMessages.value = data.messages ?? []
        nonLus.value = 0
        if (!silencieux) equipeDescendre()
    } catch (e) {
        if (!silencieux) equipeError.value = "Impossible de joindre l'équipe pour le moment."
    }
}

async function envoyerEquipe() {
    const text = equipeDraft.value.trim()
    if (!text || equipeSending.value) return
    equipeSending.value = true
    equipeError.value = null
    try {
        const data = await assistantFetch('/assistance/equipe', { method: 'POST', body: { message: text } })
        equipeMessages.value = data.messages ?? []
        equipeDraft.value = ''
        equipeDescendre()
    } catch (e) {
        equipeError.value = "Votre message n'est pas parti. Vérifiez la connexion et réessayez."
    } finally {
        equipeSending.value = false
    }
}

async function verifierNonLus() {
    if (!user.value) return
    try {
        const data = await assistantFetch('/assistance/equipe/non-lus')
        nonLus.value = data.unread ?? 0
    } catch (e) { /* silencieux : hors connexion */ }
}

onMounted(() => {
    verifierNonLus()
    equipeTimer = setInterval(() => {
        if (open.value && mode.value === 'equipe') chargerEquipe(true)
        else if (!open.value) verifierNonLus()
    }, 20000)
})
onBeforeUnmount(() => clearInterval(equipeTimer))

function scrollToBottom() {
    nextTick(() => {
        if (scrollArea.value) {
            scrollArea.value.scrollTop = scrollArea.value.scrollHeight
        }
    })
}

async function startNewConversation() {
    if (loading.value) return
    loading.value = true
    errorMessage.value = null
    flagging.value = false
    flagSent.value = false

    try {
        const qs = currentOrgUnitId.value ? `?org_unit_id=${encodeURIComponent(currentOrgUnitId.value)}` : ''
        const data = await assistantFetch('/assistant/conversation/nouvelle' + qs, { method: 'POST', body: {} })
        applyConversation(data)
    } catch (e) {
        errorMessage.value = 'Impossible de démarrer une nouvelle discussion.'
    } finally {
        loading.value = false
    }
}

function pickFiles() {
    fileInput.value?.click()
}

function onFilesChosen(event) {
    const files = Array.from(event.target.files || [])
    // Point 5 (maitrise des couts/volume) : au plus 3 pieces jointes par
    // message, cote serveur egalement (voir AssistantController).
    pendingFiles.value = [...pendingFiles.value, ...files].slice(0, 3)
    event.target.value = ''
}

function removePendingFile(index) {
    pendingFiles.value = pendingFiles.value.filter((_, i) => i !== index)
}

async function sendMessage() {
    const text = draft.value.trim()
    if ((!text && pendingFiles.value.length === 0) || sending.value || dailyLimitReached.value) return

    if (!conversationId.value) {
        await ensureLoaded()
        if (!conversationId.value) return
    }

    sending.value = true
    errorMessage.value = null

    // Affichage optimiste du message envoye (avant reponse serveur) :
    // l'API ne renvoie que la reponse de l'assistant (voir
    // AssistantController::sendMessage), pas un echo du message utilisateur.
    const optimisticMessage = {
        id: `local-${Date.now()}`,
        role: 'user',
        content: text || null,
        attachments: pendingFiles.value.map((f) => ({ original_name: f.name, mime: f.type })),
        created_at: new Date().toISOString(),
    }
    messages.value = [...messages.value, optimisticMessage]
    scrollToBottom()

    const formData = new FormData()
    if (text) formData.append('message', text)
    pendingFiles.value.forEach((file) => formData.append('files[]', file))
    if (currentOrgUnitId.value) formData.append('org_unit_id', currentOrgUnitId.value)

    draft.value = ''
    const filesSent = pendingFiles.value
    pendingFiles.value = []

    try {
        const data = await assistantFetch(`/assistant/conversation/${conversationId.value}/messages`, {
            method: 'POST',
            body: formData,
            isForm: true,
        })
        messages.value = [...messages.value, data.reply]
    } catch (e) {
        if (e.status === 429) {
            dailyLimitReached.value = true
            errorMessage.value = e.message
        } else {
            errorMessage.value = "L'assistant n'est pas disponible pour le moment. Vous pouvez écrire directement à l'équipe technique."
            // Remet le brouillon pour ne pas faire perdre le message a
            // l'utilisateur en cas de souci reseau passager.
            draft.value = text
            pendingFiles.value = filesSent
            messages.value = messages.value.filter((m) => m.id !== optimisticMessage.id)
        }
    } finally {
        sending.value = false
        scrollToBottom()
    }
}

async function sendFlag() {
    if (!flagText.value.trim() || !conversationId.value) return

    try {
        await assistantFetch(`/assistant/conversation/${conversationId.value}/signaler`, {
            method: 'POST',
            body: { message: flagText.value.trim() },
        })
        flagSent.value = true
        flagText.value = ''
        flagging.value = false
    } catch (e) {
        errorMessage.value = "Le signalement n'a pas pu être envoyé."
    }
}

watch(open, (isOpen) => {
    if (isOpen) nextTick(scrollToBottom)
})
watch(() => equipeMessages.value.length, equipeDescendre)
</script>

<template>
    <div v-if="user" class="fixed bottom-5 right-5 z-50 flex flex-col items-end gap-3" style="font-family: 'Source Sans 3', ui-sans-serif, system-ui, sans-serif;">
        <!-- Fenetre de conversation -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-3 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="w-[22rem] max-w-[92vw] h-[32rem] max-h-[75vh] rounded-2xl overflow-hidden flex flex-col shadow-glow-azure border border-white/10"
                style="background: rgba(26,32,28,0.97); backdrop-filter: blur(20px);"
            >
                <!-- En-tete -->
                <div class="flex items-center gap-3 px-4 py-3 border-b border-white/10 bg-white/[0.03]">
                    <button v-if="mode !== 'choix'" type="button" title="Retour" class="text-white/60 hover:text-white p-1.5 rounded-lg hover:bg-white/5" @click="retourChoix">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
                    </button>
                    <span class="shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-azure to-azure-dark flex items-center justify-center text-white font-serif font-bold text-[13px]">{{ initials }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-serif text-white text-[14px] leading-tight truncate">{{ mode === 'equipe' ? 'Équipe technique' : mode === 'david' ? assistantName : 'Besoin d\'aide ?' }}</p>
                        <p class="text-[11px] text-white/50 truncate">{{ mode === 'equipe' ? 'Une vraie personne vous répond' : mode === 'david' ? (assistantTagline || 'Assistant Oikonema') : 'Choisissez comment être aidé' }}</p>
                    </div>
                    <button
                        v-if="mode === 'david'"
                        type="button"
                        title="Signaler un problème ou une idée à l'équipe technique"
                        class="text-white/40 hover:text-azure-light p-1.5 rounded-lg hover:bg-white/5 transition-colors"
                        @click="flagging = !flagging; flagSent = false"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M3 2.75a.75.75 0 0 1 1.5 0v.443c1.11-.36 2.318-.55 3.5-.55 1.32 0 2.62.24 3.816.71a.75.75 0 0 0 .684-.05l.176-.107a5.5 5.5 0 0 1 4.286-.62.75.75 0 0 1 .538.72v6.208a.75.75 0 0 1-1.038.694 4 4 0 0 0-3.106.45l-.176.107a2.25 2.25 0 0 1-2.052.15A8.03 8.03 0 0 0 8 9.643c-1.06 0-2.1.163-3 .48V17.25a.75.75 0 0 1-1.5 0V2.75Z" /></svg>
                    </button>
                    <button v-if="mode === 'david'" type="button" title="Nouvelle discussion" class="text-white/40 hover:text-azure-light p-1.5 rounded-lg hover:bg-white/5 transition-colors" @click="startNewConversation">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                    </button>
                    <button type="button" title="Fermer" class="text-white/40 hover:text-white p-1.5 rounded-lg hover:bg-white/5 transition-colors" @click="open = false">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                    </button>
                </div>

                <!-- Choix -->
                <div v-if="mode === 'choix'" class="flex-1 flex flex-col gap-3 p-4 justify-center">
                    <button type="button" class="flex-1 max-h-48 rounded-2xl border border-white/10 bg-white/[0.05] hover:bg-white/[0.09] transition px-4 py-5 flex flex-col items-center justify-center gap-2 text-center" @click="choisir('david')">
                        <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-azure to-azure-dark flex items-center justify-center text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" /></svg>
                        </span>
                        <span class="font-serif text-white text-[15px]">Discuter avec l'assistant</span>
                        <span class="text-[12px] text-white/55 leading-snug">{{ assistantName }} répond tout de suite à vos questions d'utilisation</span>
                    </button>
                    <button type="button" class="relative flex-1 max-h-48 rounded-2xl border border-white/10 bg-white/[0.05] hover:bg-white/[0.09] transition px-4 py-5 flex flex-col items-center justify-center gap-2 text-center" @click="choisir('equipe')">
                        <span v-if="nonLus" class="absolute top-3 right-3 min-w-[1.4rem] h-6 px-1.5 rounded-full bg-sanctuary text-white text-xs font-semibold flex items-center justify-center">{{ nonLus }}</span>
                        <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-gold to-gold-dark flex items-center justify-center text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                        </span>
                        <span class="font-serif text-white text-[15px]">Écrire à l'équipe technique</span>
                        <span class="text-[12px] text-white/55 leading-snug">Un vrai message, lu par une personne, qui vous répond ici</span>
                    </button>
                </div>

                <!-- Equipe technique -->
                <template v-else-if="mode === 'equipe'">
                    <div ref="equipeScroll" class="flex-1 overflow-y-auto px-4 py-3 space-y-3">
                        <p v-if="!equipeMessages.length" class="text-white/55 text-[13px] leading-relaxed">Écrivez votre message ci-dessous : l'équipe technique le lira et vous répondra dans cette même fenêtre. Pensez à dire ce que vous essayiez de faire.</p>
                        <div v-for="m in equipeMessages" :key="m.id" class="flex" :class="m.from_staff ? 'justify-start' : 'justify-end'">
                            <div class="max-w-[85%] rounded-2xl px-3.5 py-2.5 text-[13.5px] leading-relaxed whitespace-pre-wrap break-words"
                                :class="m.from_staff ? 'bg-white/[0.06] text-white/90 border border-white/10 rounded-bl-sm' : 'bg-gradient-to-br from-azure to-azure-dark text-white rounded-br-sm'">
                                <span v-if="m.from_staff" class="block text-[10.5px] uppercase tracking-wide text-gold mb-0.5">Équipe technique</span>
                                {{ m.body }}
                            </div>
                        </div>
                        <p v-if="equipeError" class="text-[12px] text-rose-400 bg-rose-400/10 border border-rose-400/30 rounded-xl px-3 py-2">{{ equipeError }}</p>
                    </div>
                    <form class="p-3 border-t border-white/10 flex items-end gap-2" @submit.prevent="envoyerEquipe">
                        <textarea v-model="equipeDraft" rows="2" placeholder="Écrivez à l'équipe technique…"
                            class="flex-1 resize-none text-[13.5px] bg-white/5 border border-white/10 rounded-2xl px-3.5 py-2.5 text-white/90 placeholder-white/30 focus:outline-none"
                            @keydown.enter.exact.prevent="envoyerEquipe"></textarea>
                        <button type="submit" :disabled="equipeSending || !equipeDraft.trim()" class="shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-azure to-azure-dark flex items-center justify-center text-white disabled:opacity-40">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M3.105 2.288a.75.75 0 0 0-.826.95l1.414 4.926A1.5 1.5 0 0 0 5.135 9.25h6.115a.75.75 0 0 1 0 1.5H5.135a1.5 1.5 0 0 0-1.442 1.086l-1.414 4.926a.75.75 0 0 0 .826.95 28.897 28.897 0 0 0 15.293-7.154.75.75 0 0 0 0-1.115A28.897 28.897 0 0 0 3.105 2.288Z" /></svg>
                        </button>
                    </form>
                </template>

                <!-- Assistant -->
                <template v-else>
                <!-- Panneau "signaler" -->
                <div v-if="flagging" class="px-4 py-3 border-b border-white/10 bg-white/[0.02] space-y-2">
                    <p class="text-[12px] text-white/60">Décrivez le problème ou l'idée à transmettre à l'équipe technique :</p>
                    <textarea v-model="flagText" rows="2" class="w-full text-[13px] rounded-lg bg-white/5 border border-white/10 text-white/90 px-2.5 py-2 focus:outline-none focus:border-azure/60 resize-none" placeholder="Ex. je ne trouve pas où modifier une annonce déjà publiée..."></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="text-[12px] text-white/50 hover:text-white px-2 py-1" @click="flagging = false">Annuler</button>
                        <button type="button" class="text-[12px] text-white bg-azure hover:bg-azure-dark rounded-full px-3 py-1 font-medium" @click="sendFlag">Envoyer</button>
                    </div>
                </div>
                <div v-if="flagSent" class="px-4 py-2 border-b border-white/10 bg-forest/10">
                    <p class="text-[12px] text-forest">Merci, c'est transmis à l'équipe technique.</p>
                </div>

                <!-- Messages -->
                <div ref="scrollArea" class="flex-1 overflow-y-auto px-4 py-3 space-y-3">
                    <div v-if="loading && messages.length === 0" class="text-center text-white/40 text-[12px] pt-8">Chargement…</div>

                    <p v-if="!loading && messages.length === 0" class="text-white/50 text-[13px] leading-relaxed">
                        Shalom et bienvenue 👋 Je suis {{ assistantName }}, {{ assistantTagline || 'votre assistant Oikonema' }}. Posez-moi une question sur le fonctionnement de la plateforme, ou décrivez-moi une difficulté rencontrée.
                    </p>

                    <div v-for="message in messages" :key="message.id" class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div
                            class="max-w-[85%] rounded-2xl px-3.5 py-2.5 text-[13.5px] leading-relaxed whitespace-pre-wrap break-words"
                            :class="message.role === 'user'
                                ? 'bg-gradient-to-br from-azure to-azure-dark text-white rounded-br-sm'
                                : 'bg-white/[0.06] text-white/90 border border-white/10 rounded-bl-sm'"
                        >
                            <div v-if="message.attachments?.length" class="flex flex-wrap gap-1.5 mb-1.5">
                                <span v-for="(att, i) in message.attachments" :key="i" class="text-[10.5px] px-2 py-0.5 rounded-full bg-black/15 border border-black/10">📎 {{ att.original_name }}</span>
                            </div>
                            <span v-if="message.content">{{ message.content }}</span>
                        </div>
                    </div>

                    <div v-if="sending" class="flex justify-start">
                        <div class="rounded-2xl rounded-bl-sm px-3.5 py-2.5 bg-white/[0.06] border border-white/10">
                            <span class="flex gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-white/50 animate-bounce" style="animation-delay:0ms"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-white/50 animate-bounce" style="animation-delay:120ms"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-white/50 animate-bounce" style="animation-delay:240ms"></span>
                            </span>
                        </div>
                    </div>

                    <p v-if="errorMessage" class="text-[12px] text-rose-400 bg-rose-400/10 border border-rose-400/30 rounded-xl px-3 py-2">{{ errorMessage }}
                        <button type="button" class="block mt-1 underline text-azure-light font-semibold" @click="choisir('equipe')">Écrire à l'équipe technique</button></p>
                    <p v-if="dailyLimitReached" class="text-[12px] text-azure-light bg-azure/10 border border-azure/30 rounded-xl px-3 py-2">Le quota de messages du jour pour votre ministère est atteint. Réessayez demain.</p>
                </div>

                <!-- Pieces jointes en attente -->
                <div v-if="pendingFiles.length" class="px-4 pt-2 flex flex-wrap gap-1.5">
                    <span v-for="(file, i) in pendingFiles" :key="i" class="text-[11px] text-white/70 bg-white/5 border border-white/10 rounded-full pl-2.5 pr-1 py-1 flex items-center gap-1">
                        📎 {{ file.name }}
                        <button type="button" class="text-white/40 hover:text-white ml-1" @click="removePendingFile(i)">✕</button>
                    </span>
                </div>

                <!-- Saisie -->
                <form class="p-3 border-t border-white/10 flex items-end gap-2" @submit.prevent="sendMessage">
                    <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/webp,application/pdf" multiple class="hidden" @change="onFilesChosen" />
                    <button type="button" title="Joindre une image ou un document" class="shrink-0 text-white/50 hover:text-azure-light p-2 rounded-lg hover:bg-white/5 transition-colors" :disabled="dailyLimitReached" @click="pickFiles">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M15.621 4.379a3 3 0 0 0-4.242 0l-7 7a3 3 0 0 0 4.241 4.243h.001l.497-.5a.75.75 0 0 1 1.064 1.057l-.498.501-.002.002a4.5 4.5 0 0 1-6.364-6.364l7-7a4.5 4.5 0 0 1 6.368 6.36l-3.455 3.553A2.625 2.625 0 1 1 9.52 9.52l3.45-3.451a.75.75 0 1 1 1.061 1.06l-3.45 3.451a1.125 1.125 0 0 0 1.587 1.595l3.454-3.553a3 3 0 0 0 0-4.242Z" clip-rule="evenodd" /></svg>
                    </button>
                    <textarea
                        v-model="draft"
                        rows="1"
                        :disabled="dailyLimitReached"
                        placeholder="Écrivez votre message…"
                        class="flex-1 resize-none text-[13.5px] bg-white/5 border border-white/10 rounded-2xl px-3.5 py-2.5 text-white/90 placeholder-white/30 focus:outline-none focus:border-azure/60 max-h-28"
                        @keydown.enter.exact.prevent="sendMessage"
                    ></textarea>
                    <button
                        type="submit"
                        class="shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-azure to-azure-dark flex items-center justify-center text-white disabled:opacity-40 transition-opacity"
                        :disabled="sending || dailyLimitReached || (!draft.trim() && pendingFiles.length === 0)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" /></svg>
                    </button>
                </form>
                </template>
            </div>
        </transition>

        <!-- Bouton flottant -->
        <button
            type="button"
            class="relative w-14 h-14 rounded-full bg-gradient-to-br from-azure to-azure-dark shadow-glow-azure flex items-center justify-center text-white hover:scale-105 transition-transform"
            :aria-label="open ? 'Fermer l\'assistant' : 'Ouvrir l\'assistant Oikonema'"
            @click="toggleOpen"
        >
            <span v-if="nonLus && !open" class="absolute -top-1 -right-1 min-w-[1.3rem] h-5 px-1 rounded-full bg-sanctuary text-white text-[11px] font-semibold flex items-center justify-center">{{ nonLus }}</span>
            <svg v-if="!open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path fill-rule="evenodd" d="M4.848 2.771A49.144 49.144 0 0 1 12 2.25c2.43 0 4.817.178 7.152.52 1.978.292 3.348 2.024 3.348 3.97v6.02c0 1.946-1.37 3.678-3.348 3.97a48.901 48.901 0 0 1-3.476.383.39.39 0 0 0-.297.17l-2.755 4.133a.75.75 0 0 1-1.248 0l-2.755-4.133a.39.39 0 0 0-.297-.17 48.9 48.9 0 0 1-3.476-.384c-1.978-.29-3.348-2.024-3.348-3.97V6.741c0-1.946 1.37-3.678 3.348-3.97Z" clip-rule="evenodd" /></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-6 h-6"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
        </button>
    </div>
</template>
