<script setup>
import { ref } from 'vue'
import { canInstall, isIosDevice, isStandaloneNow, promptInstall } from '@/pwaInstall'

/**
 * Bouton "Installer l'application" affiche directement sur le tableau de
 * bord (chantier "application telechargeable", 2026-09-19). Voir
 * pwaInstall.js pour le choix technique et le raisonnement complet.
 *
 * Ne s'affiche pas du tout si l'application est deja installee
 * (isStandaloneNow), ni si le navigateur ne propose aucun des deux
 * mecanismes (ni beforeinstallprompt, ni iOS) - jamais un bouton qui ne
 * ferait rien au clic.
 */
const showIosInstructions = ref(false)

async function onClick() {
    if (isIosDevice.value) {
        showIosInstructions.value = true
        return
    }

    await promptInstall()
}
</script>

<template>
    <div v-if="!isStandaloneNow && (canInstall || isIosDevice)" class="relative shrink-0">
        <button
            type="button"
            @click="onClick"
            class="flex items-center gap-1.5 text-sm text-graphite bg-gold/15 hover:bg-gold/25 border border-gold/40 rounded-full px-4 py-2 transition-colors"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13m0 0-4-4m4 4 4-4M5 19h14" />
            </svg>
            <span class="hidden sm:inline">Installer l'application</span>
            <span class="sm:hidden">Installer</span>
        </button>

        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showIosInstructions"
                class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 px-4"
                @click.self="showIosInstructions = false"
            >
                <div class="max-w-sm w-full rounded-2xl bg-white p-6 shadow-card-hover">
                    <p class="text-base font-semibold text-graphite mb-2">Installer sur cet iPhone/iPad</p>
                    <p class="text-sm text-graphite/75">
                        Appuyez sur
                        <span class="inline-flex items-center justify-center w-4 h-4 align-middle mx-0.5">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13m0-13l-4 4m4-4l4 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
                        </span>
                        (Partager) en bas de Safari, puis « Sur l'écran d'accueil ».
                    </p>
                    <button
                        type="button"
                        @click="showIosInstructions = false"
                        class="mt-4 inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark text-white rounded-xl px-4 py-2 text-sm font-semibold"
                    >
                        Compris
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>
