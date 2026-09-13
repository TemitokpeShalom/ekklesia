<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

/**
 * Indicateur de connexion (2026-09-13) : premiere brique visible du
 * chantier hors connexion (point "un indicateur visible" de la proposition
 * ecrite du 13/09) - signale l'etat de la connexion, ne change rien au
 * fonctionnement normal de la plateforme.
 *
 * Deuxieme pierre (meme jour) : quand une liste de membres a deja ete
 * enregistree sur cet appareil (voir Members/Index.vue), un lien apparait
 * ici pour la consulter en lecture seule pendant la coupure
 * (membres-hors-connexion.html, page statique precachee par le service
 * worker). Montee globalement (voir app.js), comme InstallPrompt.vue.
 */
const isOffline = ref(typeof navigator !== 'undefined' ? !navigator.onLine : false)
const hasCachedMembers = ref(false)

function refreshCachedMembersFlag() {
    try {
        hasCachedMembers.value = !!localStorage.getItem('oikonema-membres-hors-connexion')
    } catch (e) {
        hasCachedMembers.value = false
    }
}

function update() {
    isOffline.value = !navigator.onLine
    if (isOffline.value) {
        refreshCachedMembersFlag()
    }
}

onMounted(() => {
    window.addEventListener('online', update)
    window.addEventListener('offline', update)
    if (isOffline.value) {
        refreshCachedMembersFlag()
    }
})

onUnmounted(() => {
    window.removeEventListener('online', update)
    window.removeEventListener('offline', update)
})
</script>

<template>
    <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="isOffline" class="fixed top-0 inset-x-0 z-[70] bg-graphite text-white text-sm text-center py-2 px-4">
            <span>Hors connexion. Certaines actions ne fonctionneront pas tant que la connexion n'est pas rétablie.</span>
            <a
                v-if="hasCachedMembers"
                href="/membres-hors-connexion.html"
                class="ml-2 font-semibold underline underline-offset-2 hover:text-gold-soft"
            >
                Voir la liste des membres enregistrée
            </a>
        </div>
    </transition>
</template>
