/**
 * File d'attente hors connexion pour le parcours de disciple (chantier
 * "hors connexion", sixieme pierre, 2026-09-19 : meme principe que les
 * cultes, membres et sacrements, voir offlineCultesQueue.js pour le
 * raisonnement complet). Aucun fichier dans ce formulaire, donc aucune
 * limite particuliere ici : tous les champs sont gardes hors connexion.
 */
const STORAGE_KEY = 'oikonema-parcours-en-attente';

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
        // Stockage plein ou indisponible : tant pis, jamais d'erreur
        // bloquante pour l'utilisateur a cause de ca.
    }
}

export function queueParcours(orgUnitId, data) {
    const queue = readQueue();

    queue.push({
        localId: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        orgUnitId,
        data,
        createdAt: new Date().toISOString(),
    });

    writeQueue(queue);
}

export function countQueuedParcours() {
    return readQueue().length;
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta ? meta.getAttribute('content') : '';
}

export async function syncQueuedParcours() {
    let queue = readQueue();
    let envoyes = 0;

    for (const item of [...queue]) {
        try {
            const response = await fetch(`/org-units/${item.orgUnitId}/parcours`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(item.data),
            });

            if (!response.ok) {
                break;
            }

            queue = queue.filter((q) => q.localId !== item.localId);
            writeQueue(queue);
            envoyes += 1;
        } catch (e) {
            break;
        }
    }

    return { envoyes, restants: queue.length };
}
