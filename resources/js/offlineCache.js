/**
 * Pages disponibles hors connexion (2026-11-01).
 *
 * Avant ce chantier, seules les SAISIES pouvaient se faire sans reseau, et
 * seulement sur une page deja ouverte : ouvrir ou changer de page exigeait la
 * connexion. Ici, l'application installee sur le telephone peut s'ouvrir et
 * se parcourir sans reseau :
 *  - le service worker (public/sw.js) garde, sur CET appareil, la derniere
 *    version de chaque page de travail visitee ;
 *  - ce module prepare en avance les pages utiles de l'entite (listes et
 *    formulaires de saisie), pour qu'elles s'ouvrent meme si on ne les a
 *    jamais visitees ;
 *  - tout est efface a la deconnexion, ou si une autre personne se connecte
 *    sur le meme appareil (voir effacerSiAutreUtilisateur).
 * Les messages temporaires (codes, liens d'invitation, confirmations) ne sont
 * jamais gardes : le service worker les retire avant d'enregistrer.
 */
const CACHE_DONNEES = 'oikonema-data-v1'
const CLE_UTILISATEUR = 'oikonema-hors-connexion-utilisateur'
const CLE_PREPARE = 'oikonema-hors-connexion-prepare-'
const CLE_ACTIFS = 'oikonema-hors-connexion-actifs'
const DELAI_PREPARATION_MS = 3 * 60 * 60 * 1000

// Pages preparees pour chaque entite : les listes et les formulaires de saisie.
const PAGES_ENTITE = [
    '', '/membres', '/membres/nouveau', '/trombinoscope',
    '/documents', '/documents/rapports', '/documents/calendrier',
    '/cultes', '/cultes/nouveau',
    '/parcours', '/parcours/nouveau',
    '/sacrements', '/sacrements/nouveau',
    '/equipes', '/finances', '/finances/nouveau',
    '/inventaire', '/inventaire/nouveau',
    '/rapport-activites', '/annonces', '/annonces/nouvelle', '/signalements',
    '/bibliotheque',
]

const pause = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

function lire(cle) {
    try {
        return localStorage.getItem(cle)
    } catch (e) {
        return null
    }
}

function ecrire(cle, valeur) {
    try {
        localStorage.setItem(cle, valeur)
    } catch (e) {
        // Stockage indisponible : le mode hors connexion sera simplement moins complet.
    }
}

export function serviceWorkerDisponible() {
    return typeof navigator !== 'undefined' && 'serviceWorker' in navigator
}

export async function enregistrerServiceWorker() {
    if (!serviceWorkerDisponible()) {
        return false
    }

    try {
        await navigator.serviceWorker.register('/sw.js')
        await navigator.serviceWorker.ready

        return true
    } catch (e) {
        return false
    }
}

/** Efface toutes les pages gardees sur l'appareil (deconnexion, changement de personne). */
export async function effacerDonneesHorsConnexion() {
    try {
        if (typeof caches !== 'undefined') {
            await caches.delete(CACHE_DONNEES)
        }

        const aSupprimer = []
        for (let i = 0; i < localStorage.length; i += 1) {
            const cle = localStorage.key(i)
            if (cle && (cle.startsWith(CLE_PREPARE) || cle === CLE_ACTIFS)) {
                aSupprimer.push(cle)
            }
        }
        aSupprimer.forEach((cle) => localStorage.removeItem(cle))
    } catch (e) {
        // Rien a faire : au pire, le cache sera efface a la prochaine occasion.
    }
}

/**
 * A appeler a chaque page connectee : si ce n'est plus la meme personne que
 * la derniere fois sur cet appareil, les pages gardees de l'ancienne personne
 * sont effacees avant tout autre travail.
 */
export async function effacerSiAutreUtilisateur(userId) {
    if (!userId) {
        return
    }

    const precedent = lire(CLE_UTILISATEUR)

    if (precedent && precedent !== String(userId)) {
        await effacerDonneesHorsConnexion()
    }

    ecrire(CLE_UTILISATEUR, String(userId))
}

function versionInertia() {
    try {
        return JSON.parse(document.getElementById('app').dataset.page).version || ''
    } catch (e) {
        return ''
    }
}

/**
 * Prepare en arriere-plan les pages de l'entite (une fois toutes les 3 h au
 * plus). Chaque page est demandee comme le fait l'application elle-meme ; le
 * service worker la range au passage. Les pages refusees (droits) ne sont
 * simplement pas gardees. Jamais sur connexion "economie de donnees".
 */
export async function preparerEntite(orgUnitId) {
    if (!orgUnitId || !navigator.onLine || !serviceWorkerDisponible()) {
        return
    }

    if (navigator.connection && navigator.connection.saveData) {
        return
    }

    const derniere = Number(lire(CLE_PREPARE + orgUnitId) || 0)
    if (Date.now() - derniere < DELAI_PREPARATION_MS) {
        return
    }

    // Le marqueur est pose AVANT de commencer : une navigation rapide ne
    // relance pas une deuxieme preparation en parallele.
    ecrire(CLE_PREPARE + orgUnitId, String(Date.now()))

    const version = versionInertia()
    const base = `/org-units/${orgUnitId}`

    // 1. Le "gabarit" HTML (necessaire pour ouvrir une page sans reseau).
    try {
        await fetch(base, { credentials: 'same-origin', headers: { Accept: 'text/html' } })
    } catch (e) {
        return
    }

    // 2. Chaque page, en version application (JSON), une par une et sans se presser.
    for (const suffixe of PAGES_ENTITE) {
        if (!navigator.onLine) {
            return
        }

        try {
            await fetch(base + suffixe, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'text/html, application/xhtml+xml',
                    'X-Inertia': 'true',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Inertia-Version': version,
                },
            })
        } catch (e) {
            return
        }

        await pause(150)
    }

    ecrire(CLE_ACTIFS, String(Date.now()))
}

/**
 * Garde en memoire tous les fichiers de l'application (scripts et styles de
 * chaque page) : sans cela, une page jamais ouverte n'aurait pas son code
 * hors connexion. Refait seulement quand une nouvelle version est deployee.
 */
export async function preparerFichiersApplication() {
    if (!navigator.onLine || !serviceWorkerDisponible()) {
        return
    }

    try {
        let reponse = await fetch('/build/manifest.json', { credentials: 'same-origin' })
        if (!reponse.ok) {
            reponse = await fetch('/build/.vite/manifest.json', { credentials: 'same-origin' })
        }
        if (!reponse.ok) {
            return
        }

        const manifeste = await reponse.json()
        const fichiers = new Set()
        Object.values(manifeste).forEach((entree) => {
            if (entree.file) fichiers.add(entree.file)
            ;(entree.css || []).forEach((f) => fichiers.add(f))
        })

        const signature = [...fichiers].sort().join('|')
        if (lire('oikonema-hors-connexion-fichiers') === signature) {
            return
        }

        for (const fichier of fichiers) {
            if (!navigator.onLine) {
                return
            }
            await fetch(`/build/${fichier}`, { credentials: 'same-origin' })
            await pause(40)
        }

        ecrire('oikonema-hors-connexion-fichiers', signature)
    } catch (e) {
        // Echec silencieux : sera retente a la prochaine ouverture.
    }
}

/**
 * Jeton de securite (CSRF) a jour. Une page ouverte depuis la memoire de
 * l'appareil peut porter un jeton ancien : avant d'envoyer les saisies faites
 * hors connexion, on en demande un neuf au serveur et on le place dans la page
 * (les files d'attente le lisent au moment de l'envoi).
 */
export async function rafraichirJetonCsrf() {
    try {
        const reponse = await fetch('/', {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'text/html' },
        })
        const html = await reponse.text()
        const trouve = html.match(/<meta name="csrf-token" content="([^"]+)"/)

        if (trouve) {
            const meta = document.querySelector('meta[name="csrf-token"]')
            if (meta) {
                meta.setAttribute('content', trouve[1])
            }
        }
    } catch (e) {
        // Hors connexion ou serveur injoignable : l'envoi echouera proprement et sera retente.
    }
}

export function orgUnitDepuisAdresse() {
    const trouve = window.location.pathname.match(/^\/org-units\/([0-9a-fA-F-]{36})/)

    return trouve ? trouve[1] : null
}
