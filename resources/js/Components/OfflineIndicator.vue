<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

/**
 * Indicateur de connexion (2026-09-13) : premiere brique visible du
 * chantier hors connexion (point "un indicateur visible" de la proposition
 * ecrite du 13/09) - se contente pour l'instant de signaler l'etat de la
 * connexion, ne change encore rien au fonctionnement de la plateforme.
 * Le stockage local et la synchronisation de Membres/Cultes arrivent dans
 * une prochaine livraison. Montee globalement (voir app.js), comme
 * InstallPrompt.vue.
 */
const isOffline = ref(typeof navigator !== 'undefined' ? !navigator.onLine : false)

function update() {
    isOffline.value = !navigator.onLine
}

onMounted(() => {
    window.addEventListener('online', update)
    window.addEventListener('offline', update)
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
            Hors connexion. Certaines actions ne fonctionneront pas tant que la connexion n'est pas rétablie.
        </div>
    </transition>
</template>
