import { ref } from 'vue'

/**
 * Chantier "application telechargeable" (2026-09-19, apres celui de la
 * saisie hors connexion) : bouton "Installer l'application" visible
 * directement sur le tableau de bord (demande explicite du ministere), en
 * complement du bandeau discret existant (InstallPrompt.vue, qui propose
 * l'installation automatiquement des l'arrivee sur le site, sans y toucher
 * ici par prudence puisqu'il est deja livre et fonctionnel).
 *
 * Choix technique retenu : plutot qu'un vrai fichier .apk a compiler et
 * signer (hors de portee sans outillage Android dedie, et fragile a tenir
 * a jour), le mecanisme d'installation natif du navigateur -
 * "beforeinstallprompt" sur Android/Chrome/Edge, y compris sur ordinateur
 * (un seul bouton couvre donc telephone ET ordinateur, exactement le
 * besoin exprime). Sur iOS, Apple n'expose aucune API declenchable par
 * code : instructions manuelles a la place (voir isIosDevice).
 *
 * Ecoute globale independante de celle d'InstallPrompt.vue : les deux
 * cohabitent sans se gener, le navigateur envoie le meme evenement a
 * chaque ecouteur enregistre sur window.
 */
let deferredPrompt = null

export const canInstall = ref(false)
export const isIosDevice = ref(false)
export const isStandaloneNow = ref(false)

function detectStandalone() {
    return typeof window !== 'undefined'
        && (window.matchMedia?.('(display-mode: standalone)').matches || window.navigator.standalone === true)
}

function detectIos() {
    return typeof window !== 'undefined'
        && /iphone|ipad|ipod/i.test(window.navigator.userAgent)
        && !window.MSStream
}

if (typeof window !== 'undefined') {
    isStandaloneNow.value = detectStandalone()
    isIosDevice.value = detectIos() && !isStandaloneNow.value

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault()
        deferredPrompt = event
        canInstall.value = true
    })

    window.addEventListener('appinstalled', () => {
        canInstall.value = false
        isStandaloneNow.value = true
        deferredPrompt = null
    })
}

export async function promptInstall() {
    if (!deferredPrompt) {
        return false
    }

    deferredPrompt.prompt()
    const choice = await deferredPrompt.userChoice
    deferredPrompt = null
    canInstall.value = false

    return choice.outcome === 'accepted'
}
