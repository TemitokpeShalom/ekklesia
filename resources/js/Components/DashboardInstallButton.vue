<script setup>
import { ref } from 'vue'
import { canInstall, isIosDevice, isStandaloneNow, promptInstall } from '@/pwaInstall'

/**
 * Bouton "Installer l'application" affiche directement sur le tableau de
 * bord (chantier "application telechargeable", 2026-09-19). Voir
 * pwaInstall.js pour le choix technique et le raisonnement complet.
 *
 * Corrige le 2026-09-19 (retour du ministere : bouton invisible sur
 * ordinateur/Chrome malgre un git pull + npm run build reussis) : le
 * navigateur ne declenche "beforeinstallprompt" qu'a son propre rythme (pas
 * forcement des la premiere visite apres une mise a jour, meme site
 * parfaitement installable) - un bouton qui disparaissait entierement tant
 * que cet evenement n'etait pas encore arrive donnait l'impression a tort
 * que la livraison n'avait pas fonctionne. Desormais, le bouton reste
 * TOUJOURS visible tant que l'application n'est pas deja installee
 * (isStandaloneNow) : soit il installe directement (canInstall), soit il
 * affiche des instructions adaptees (iOS, ou navigateur/moment ou
 * l'installation automatique n'est pas encore proposee).
 */
const showHelp = ref(false)

async function onClick() {
    if (canInstall.value) {
        await promptInstall()
        return
    }

    showHelp.value = true
}
</script>

<template>
    <div v-if="!isStandaloneNow" class="relative shrink-0">
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
                v-if="showHelp"
                class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 px-4"
                @click.self="showHelp = false"
            >
                <div class="max-w-sm w-full rounded-2xl bg-white p-6 shadow-card-hover">
                    <template v-if="isIosDevice">
                        <p class="text-base font-semibold text-graphite mb-2">Installer sur cet iPhone/iPad</p>
                        <p class="text-sm text-graphite/75">
                            Appuyez sur
                            <span class="inline-flex items-center justify-center w-4 h-4 align-middle mx-0.5">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13m0-13l-4 4m4-4l4 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
                            </span>
                            (Partager) en bas de Safari, puis « Sur l'écran d'accueil ».
                        </p>
                    </template>
                    <template v-else>
                        <p class="text-base font-semibold text-graphite mb-2">Installation pas encore proposée ici</p>
                        <p class="text-sm text-graphite/75">
                            Sur ordinateur ou téléphone Android, cherchez une petite icône d'installation directement
                            dans la barre d'adresse du navigateur (près de l'étoile ou des trois points, en général à
                            droite), et cliquez dessus. Si elle n'y est pas encore, réessayez un peu plus tard : le
                            navigateur (Chrome ou Edge) met parfois quelques instants à la proposer, même quand tout
                            fonctionne normalement. Cela ne marche qu'avec Chrome ou Edge, pas avec Firefox.
                        </p>
                    </template>
                    <button
                        type="button"
                        @click="showHelp = false"
                        class="mt-4 inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark text-white rounded-xl px-4 py-2 text-sm font-semibold"
                    >
                        Compris
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>
