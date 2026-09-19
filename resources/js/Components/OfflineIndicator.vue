<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { countQueuedCultes, syncQueuedCultes } from '@/offlineCultesQueue'
import { countQueuedMembres, syncQueuedMembres } from '@/offlineMembresQueue'
import { countQueuedSacrements, syncQueuedSacrements } from '@/offlineSacramentsQueue'
import { countQueuedParcours, syncQueuedParcours } from '@/offlineDiscipleshipQueue'
import { countQueuedMembresEquipe, syncQueuedMembresEquipe } from '@/offlineTeamsQueue'
import { countQueuedMouvements, syncQueuedMouvements } from '@/offlineFinancesQueue'

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
 * offlineCultesQueue.js) - ce composant affiche combien sont en attente,
 * meme reseau revenu (pour rassurer : "c'est bien pris en compte, ca part
 * tout seul"), et declenche lui-meme l'envoi des que la connexion revient,
 * sans aucune action a faire.
 *
 * Quatrieme pierre (meme jour) : meme chose pour les membres
 * (offlineMembresQueue.js). Generalise ici en une petite liste de
 * "modules" plutot que de dupliquer toute la logique une deuxieme fois -
 * chaque futur module hors connexion (sacrements, parcours de disciple...)
 * n'aura qu'une ligne a ajouter a MODULES, sans toucher au reste de ce
 * fichier.
 *
 * Cinquieme pierre (2026-09-19) : meme chose pour les sacrements
 * (offlineSacramentsQueue.js), une simple ligne ajoutee a MODULES.
 *
 * Sixieme pierre (2026-09-19) : meme chose pour le parcours de disciple
 * (offlineDiscipleshipQueue.js).
 *
 * Septieme pierre (2026-09-19) : meme chose pour l'ajout d'un benevole a
 * une equipe (offlineTeamsQueue.js) - pas la creation de l'equipe
 * elle-meme, action rare qui n'a pas besoin de fonctionner hors connexion.
 *
 * Huitieme et derniere pierre de la liste annoncee (2026-09-19) : meme
 * chose pour les mouvements financiers (offlineFinancesQueue.js). Tous les
 * modules prevus par la feuille de route "hors connexion" sont desormais
 * couverts.
 */
const MODULES = [
    { cle: 'cultes', singulier: 'culte', pluriel: 'cultes', compter: countQueuedCultes, envoyer: syncQueuedCultes },
    { cle: 'membres', singulier: 'membre', pluriel: 'membres', compter: countQueuedMembres, envoyer: syncQueuedMembres },
    { cle: 'sacrements', singulier: 'sacrement', pluriel: 'sacrements', compter: countQueuedSacrements, envoyer: syncQueuedSacrements },
    { cle: 'parcours', singulier: 'étape de parcours', pluriel: 'étapes de parcours', compter: countQueuedParcours, envoyer: syncQueuedParcours },
    { cle: 'equipes', singulier: 'ajout à une équipe', pluriel: 'ajouts à une équipe', compter: countQueuedMembresEquipe, envoyer: syncQueuedMembresEquipe },
    { cle: 'finances', singulier: 'mouvement financier', pluriel: 'mouvements financiers', compter: countQueuedMouvements, envoyer: syncQueuedMouvements },
]

const isOffline = ref(typeof navigator !== 'undefined' ? !navigator.onLine : false)
const hasCachedMembers = ref(false)
const enAttenteParModule = ref(MODULES.map(() => 0))
const synchronisationEnCours = ref(false)
const derniereSyncReussie = ref(false)

const totalEnAttente = computed(() => enAttenteParModule.value.reduce((total, n) => total + n, 0))

const detailEnAttente = computed(() =>
    MODULES
        .map((module, i) => ({ module, n: enAttenteParModule.value[i] }))
        .filter(({ n }) => n > 0)
        .map(({ module, n }) => `${n} ${n > 1 ? module.pluriel : module.singulier}`)
        .join(', ')
)

function refreshCachedMembersFlag() {
    try {
        hasCachedMembers.value = !!localStorage.getItem('oikonema-membres-hors-connexion')
    } catch (e) {
        hasCachedMembers.value = false
    }
}

function refreshQueueCounts() {
    enAttenteParModule.value = MODULES.map((module) => module.compter())
}

async function lancerSynchronisation() {
    if (synchronisationEnCours.value || totalEnAttente.value === 0) {
        return
    }

    synchronisationEnCours.value = true
    derniereSyncReussie.value = false

    let totalEnvoyes = 0

    for (let i = 0; i < MODULES.length; i += 1) {
        const { envoyes, restants } = await MODULES[i].envoyer()
        enAttenteParModule.value[i] = restants
        totalEnvoyes += envoyes
    }

    synchronisationEnCours.value = false
    derniereSyncReussie.value = totalEnvoyes > 0 && totalEnAttente.value === 0

    if (derniereSyncReussie.value) {
        setTimeout(() => {
            derniereSyncReussie.value = false
        }, 6000)
    }
}

function update() {
    isOffline.value = !navigator.onLine
    refreshQueueCounts()

    if (isOffline.value) {
        refreshCachedMembersFlag()
    } else {
        lancerSynchronisation()
    }
}

onMounted(() => {
    window.addEventListener('online', update)
    window.addEventListener('offline', update)
    refreshQueueCounts()

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
            <span v-if="totalEnAttente > 0">
                {{ ' ' }}{{ detailEnAttente }} en attente, envoi automatique au retour du réseau.
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
            v-else-if="synchronisationEnCours || totalEnAttente > 0"
            class="fixed top-0 inset-x-0 z-[70] bg-azure text-white text-sm text-center py-2 px-4"
        >
            Envoi des éléments enregistrés hors connexion en cours...
        </div>
        <div
            v-else-if="derniereSyncReussie"
            class="fixed top-0 inset-x-0 z-[70] bg-emerald-600 text-white text-sm text-center py-2 px-4"
        >
            Les éléments enregistrés hors connexion ont bien été envoyés à la plateforme.
        </div>
    </transition>
</template>
