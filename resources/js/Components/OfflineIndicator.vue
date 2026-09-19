<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { countQueuedCultes, syncQueuedCultes } from '@/offlineCultesQueue'

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
 *
 * Troisieme pierre (2026-09-19) : des cultes saisis hors connexion peuvent
 * s'accumuler sur cet appareil (voir Cultes/Create.vue et
 * offlineCultesQueue.js) - ce composant affiche desormais combien sont en
 * attente, meme reseau revenu (pour rassurer : "c'est bien pris en compte,
 * ca part tout seul"), et declenche lui-meme l'envoi des que la connexion
 * revient, sans aucune action a faire.
 */
const isOffline = ref(typeof navigator !== 'undefined' ? !navigator.onLine : false)
const hasCachedMembers = ref(false)
const cultesEnAttente = ref(0)
const synchronisationEnCours = ref(false)
const derniereSyncReussie = ref(false)

function refreshCachedMembersFlag() {
    try {
        hasCachedMembers.value = !!localStorage.getItem('oikonema-membres-hors-connexion')
    } catch (e) {
        hasCachedMembers.value = false
    }
}

function refreshQueueCount() {
    cultesEnAttente.value = countQueuedCultes()
}

async function lancerSynchronisation() {
    if (synchronisationEnCours.value || countQueuedCultes() === 0) {
        return
    }

    synchronisationEnCours.value = true
    derniereSyncReussie.value = false

    const { envoyes, restants } = await syncQueuedCultes()

    cultesEnAttente.value = restants
    synchronisationEnCours.value = false
    derniereSyncReussie.value = envoyes > 0 && restants === 0

    if (derniereSyncReussie.value) {
        setTimeout(() => {
            derniereSyncReussie.value = false
        }, 6000)
    }
}

function update() {
    isOffline.value = !navigator.onLine
    refreshQueueCount()

    if (isOffline.value) {
        refreshCachedMembersFlag()
    } else {
        lancerSynchronisation()
    }
}

onMounted(() => {
    window.addEventListener('online', update)
    window.addEventListener('offline', update)
    refreshQueueCount()

    if (isOffline.value) {
        refreshCachedMembersFlag()
    } else {
        lancerSynchronisation()
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
            <span v-if="cultesEnAttente > 0">
                {{ ' ' }}{{ cultesEnAttente }} culte{{ cultesEnAttente > 1 ? 's' : '' }} en attente, envoi automatique au retour du réseau.
            </span>
            <a
                v-if="hasCachedMembers"
                href="/membres-hors-connexion.html"
                class="ml-2 font-semibold underline underline-offset-2 hover:text-gold-soft"
            >
                Voir la liste des membres enregistrée
            </a>
        </div>
        <div
            v-else-if="synchronisationEnCours || cultesEnAttente > 0"
            class="fixed top-0 inset-x-0 z-[70] bg-azure text-white text-sm text-center py-2 px-4"
        >
            Envoi des cultes enregistrés hors connexion en cours...
        </div>
        <div
            v-else-if="derniereSyncReussie"
            class="fixed top-0 inset-x-0 z-[70] bg-emerald-600 text-white text-sm text-center py-2 px-4"
        >
            Les cultes enregistrés hors connexion ont bien été envoyés à la plateforme.
        </div>
    </transition>
</template>
