/**
 * File d'attente hors connexion pour l'ajout d'un membre a une equipe
 * (chantier "hors connexion", septieme pierre, 2026-09-19). Ici, ce n'est
 * pas la creation d'une equipe qui a besoin de fonctionner hors connexion
 * (action rare, faite une fois), mais l'ajout d'un benevole a une equipe
 * deja existante (voir Teams/Membres.vue) - c'est ce qui peut arriver
 * pendant un culte sans reseau. Meme principe que les autres files
 * d'attente, voir offlineCultesQueue.js pour le raisonnement complet.
 */
const STORAGE_KEY = 'oikonema-equipes-membres-en-attente';

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

export function queueMembreEquipe(orgUnitId, equipeId, data) {
    const queue = readQueue();

    queue.push({
        localId: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        orgUnitId,
        equipeId,
        data,
        createdAt: new Date().toISOString(),
    });

    writeQueue(queue);
}

export function countQueuedMembresEquipe() {
    return readQueue().length;
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta ? meta.getAttribute('content') : '';
}

export async function syncQueuedMembresEquipe() {
    let queue = readQueue();
    let envoyes = 0;

    for (const item of [...queue]) {
        // Limite de temps volontaire (2026-09-19, voir offlineCultesQueue.js
        // pour le raisonnement complet) : sans elle, un envoi peut rester
        // bloque indefiniment sur un reseau instable, sans jamais avertir.
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 20000);

        try {
            const response = await fetch(`/org-units/${item.orgUnitId}/equipes/${item.equipeId}/membres`, {
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
