/**
 * Fabrique de files d'attente hors connexion (2026-11-01). Meme principe que
 * les files existantes (offlineCultesQueue.js...) : un element saisi sans
 * reseau est garde sur CET appareil (localStorage, jamais partage), puis
 * envoye au serveur des que la connexion revient (voir OfflineIndicator.vue).
 *
 * Difference voulue avec les premieres files : un element que le serveur
 * REFUSE definitivement (donnees invalides 422, droits insuffisants 403,
 * entite introuvable 404) ne bloque plus tous les suivants. Il est range dans
 * une liste "refuses" (visible dans le bandeau, a ressaisir) et l'envoi
 * continue avec le suivant. Les problemes passagers (reseau coupe, session
 * ou jeton expire 401/419, serveur occupe 5xx, trop de requetes 429) arretent
 * l'envoi sans rien perdre : l'element reste dans la file, on reessaiera.
 */
const ARRET_TEMPORAIRE = [401, 408, 419, 429]

function lire(cle) {
    try {
        const raw = localStorage.getItem(cle)

        return raw ? JSON.parse(raw) : []
    } catch (e) {
        return []
    }
}

function ecrire(cle, valeur) {
    try {
        localStorage.setItem(cle, JSON.stringify(valeur))
    } catch (e) {
        // Stockage plein ou indisponible : jamais d'erreur bloquante.
    }
}

function jetonCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]')

    return meta ? meta.getAttribute('content') : ''
}

export function createOfflineQueue({ storageKey, endpoint }) {
    const cleRefuses = `${storageKey}-refuses`

    function mettreEnFile(orgUnitId, data) {
        const file = lire(storageKey)

        file.push({
            localId: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
            orgUnitId,
            data,
            createdAt: new Date().toISOString(),
        })

        ecrire(storageKey, file)
    }

    function compter() {
        return lire(storageKey).length
    }

    function compterRefuses() {
        return lire(cleRefuses).length
    }

    function viderRefuses() {
        ecrire(cleRefuses, [])
    }

    async function envoyer() {
        let file = lire(storageKey)
        let envoyes = 0

        for (const item of [...file]) {
            // Limite de temps : un fetch peut attendre sans fin sur un reseau
            // instable. Passe 20 s, on abandonne proprement cet envoi.
            const controller = new AbortController()
            const timeoutId = setTimeout(() => controller.abort(), 20000)

            try {
                const response = await fetch(endpoint(item.orgUnitId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': jetonCsrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(item.data),
                    signal: controller.signal,
                })

                if (response.ok) {
                    file = file.filter((q) => q.localId !== item.localId)
                    ecrire(storageKey, file)
                    envoyes += 1
                } else if (response.status >= 500 || ARRET_TEMPORAIRE.includes(response.status)) {
                    break
                } else {
                    // Refus definitif (422, 403, 404...) : on met de cote et on continue.
                    file = file.filter((q) => q.localId !== item.localId)
                    ecrire(storageKey, file)
                    ecrire(cleRefuses, [...lire(cleRefuses), { ...item, statut: response.status }])
                }
            } catch (e) {
                break
            } finally {
                clearTimeout(timeoutId)
            }
        }

        return { envoyes, restants: file.length }
    }

    return { mettreEnFile, compter, compterRefuses, viderRefuses, envoyer }
}
