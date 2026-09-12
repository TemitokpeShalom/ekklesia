<script setup>
import { ref, onMounted } from 'vue'

/**
 * Bandeau d'installation de l'application (retour du ministere, 2026-09-12 :
 * "quand quelqu'un va sur le site avec son telephone... il faut que ca
 * demande a se telecharger et a s'installer comme une application"). Monte
 * UNE SEULE FOIS, globalement, comme AssistantWidget.vue (voir app.js) -
 * mais SANS se cacher pour une personne non connectee : le cas vise en
 * premier est justement quelqu'un qui recoit un lien d'inscription
 * (invitation, code de rattachement) et n'a donc pas encore de compte.
 *
 * Deux mecanismes bien distincts, le systeme d'exploitation du telephone
 * decidant lequel s'applique - impossible a contourner cote code :
 * - Android/Chrome/Edge : evenement natif "beforeinstallprompt", intercepte
 *   pour proposer notre propre bouton plutot que le mini-encart discret du
 *   navigateur - se declenche seul si l'appareil juge le site installable
 *   (manifeste + service worker enregistres, voir sw.js).
 * - iOS/Safari : Apple n'expose AUCUN evenement ni API pour declencher
 *   l'installation par code, quelle que soit la plateforme - seul un geste
 *   manuel (bouton Partager -> "Sur l'ecran d'accueil") le permet. On
 *   detecte iOS et on affiche a la place un mode d'emploi illustre, jamais
 *   une fausse promesse d'installation automatique.
 */
const showAndroidPrompt = ref(false)
const showIosInstructions = ref(false)
let deferredPrompt = null

function isStandalone() {
    return window.matchMedia?.('(display-mode: standalone)').matches || window.navigator.standalone === true
}

function isIos() {
    return /iphone|ipad|ipod/i.test(window.navigator.userAgent) && !window.MSStream
}

onMounted(() => {
    if (isStandalone()) return // deja installee/ouverte comme application : rien a proposer

    if ('serviceWorker' in navigator) {
        // Enregistrement necessaire au critere d'installabilite (Android/
        // Chrome) - voir sw.js, volontairement minimal (pas de mode hors
        // ligne pour l'instant, l'application a besoin du serveur).
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Echec silencieux : au pire, pas de proposition d'installation,
            // jamais un ecran casse pour autant.
        })
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault()
        deferredPrompt = event
        showAndroidPrompt.value = true
    })

    window.addEventListener('appinstalled', () => {
        showAndroidPrompt.value = false
        deferredPrompt = null
    })

    if (isIos()) {
        // Pas d'evenement equivalent sur iOS : on propose directement le
        // mode d'emploi, sans attendre un signal qui ne viendra jamais.
        showIosInstructions.value = true
    }
})

async function installNow() {
    if (!deferredPrompt) return
    deferredPrompt.prompt()
    await deferredPrompt.userChoice
    deferredPrompt = null
    showAndroidPrompt.value = false
}

function dismiss() {
    showAndroidPrompt.value = false
    showIosInstructions.value = false
}
</script>

<template>
    <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-3"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showAndroidPrompt || showIosInstructions"
            class="fixed bottom-5 left-1/2 -translate-x-1/2 z-[60] w-[92vw] max-w-sm rounded-2xl shadow-glow-azure border border-white/10 px-4 py-3.5 flex items-start gap-3"
            style="background: rgba(26,32,28,0.97); backdrop-filter: blur(20px);">
            <img src="/images/oikonema-icon.png" alt="" class="w-10 h-10 rounded-xl shrink-0 mt-0.5" />

            <div class="min-w-0 flex-1">
                <template v-if="showAndroidPrompt">
                    <p class="text-sm font-semibold text-white">Installer OIKONEMA</p>
                    <p class="text-xs text-white/60 mt-0.5 mb-2.5">Un accès plus rapide, comme une application, directement depuis votre écran d'accueil.</p>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="installNow"
                            class="bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-4 py-1.5 text-xs font-semibold">
                            Installer
                        </button>
                        <button type="button" @click="dismiss" class="text-xs text-white/50 hover:text-white px-2 py-1.5">Plus tard</button>
                    </div>
                </template>

                <template v-else-if="showIosInstructions">
                    <p class="text-sm font-semibold text-white">Installer OIKONEMA sur cet iPhone/iPad</p>
                    <p class="text-xs text-white/60 mt-0.5">
                        Appuyez sur
                        <span class="inline-flex items-center justify-center w-4 h-4 align-middle mx-0.5">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13m0-13l-4 4m4-4l4 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
                        </span>
                        (Partager) en bas de Safari, puis « Sur l'écran d'accueil ».
                    </p>
                    <button type="button" @click="dismiss" class="text-xs text-white/50 hover:text-white mt-2 px-0 py-1">Compris</button>
                </template>
            </div>

            <button type="button" title="Fermer" @click="dismiss" class="text-white/30 hover:text-white shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
            </button>
        </div>
    </transition>
</template>
