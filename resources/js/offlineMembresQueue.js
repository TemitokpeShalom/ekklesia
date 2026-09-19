/**
 * File d'attente hors connexion pour les membres (chantier "hors
 * connexion", quatrieme pierre, 2026-09-19 : meme principe que les cultes,
 * voir offlineCultesQueue.js pour le raisonnement complet).
 *
 * Limite volontaire de cette pierre : les photos (membre et conjoint) ne
 * sont PAS gardees hors connexion - une image peut peser plusieurs
 * megaoctets, et le stockage du navigateur utilise ici (localStorage) est
 * limite a quelques megaoctets au total pour tout le site, deja partage
 * avec la file des cultes et le cache de la liste des membres. Le
 * formulaire (voir Members/Create.vue) desactive donc les deux champs
 * photo tant que l'appareil est hors connexion, avec une note expliquant
 * qu'elles pourront etre ajoutees plus tard en modifiant la fiche - plutot
 * que de risquer une erreur de stockage silencieuse ou de faire echouer
 * toute la file a cause d'un seul gros fichier.
 */
const STORAGE_KEY = 'oikonema-membres-en-attente';

function readQueue() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        return raw ? JSON.parse(raw) : [];
    } catch (e) {
        return [];
    }
}

function writeQueue(queue) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(queue));
    } catch (e) {
        // Stockage plein ou indisponible (navigation privee...) : tant pis,
        // jamais d'erreur bloquante pour l'utilisateur a cause de ca.
    }
}

export function queueMembre(orgUnitId, data) {
    const queue = readQueue();

    queue.push({
        localId: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        orgUnitId,
        data,
        createdAt: new Date().toISOString(),
    });

    writeQueue(queue);
}

export function countQueuedMembres() {
    return readQueue().length;
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta ? meta.getAttribute('content') : '';
}

/**
 * Meme logique que syncQueuedCultes (voir offlineCultesQueue.js) : un par
 * un, dans l'ordre, on s'arrete au premier probleme pour ne rien perdre ni
 * envoyer dans le desordre.
 */
export async function syncQueuedMembres() {
    let queue = readQueue();
    let envoyes = 0;

    for (const item of [...queue]) {
        // Limite de temps volontaire (2026-09-19, voir offlineCultesQueue.js
        // pour le raisonnement complet) : sans elle, un envoi peut rester
        // bloque indefiniment sur un reseau instable, sans jamais avertir.
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 20000);

        try {
            const response = await fetch(`/org-units/${item.orgUnitId}/membres`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(item.data),
                signal: controller.signal,
            });

            if (!response.ok) {
                break;
            }

            queue = queue.filter((q) => q.localId !== item.localId);
            writeQueue(queue);
            envoyes += 1;
        } catch (e) {
            break;
        } finally {
            clearTimeout(timeoutId);
        }
    }

    return { envoyes, restants: queue.length };
}
