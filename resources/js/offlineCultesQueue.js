/**
 * File d'attente hors connexion pour les cultes (chantier "hors connexion",
 * troisieme pierre, 2026-09-19, demande explicite du ministere : une eglise
 * sans reseau au moment du culte doit quand meme pouvoir renseigner les
 * informations, qui se synchronisent automatiquement des que la connexion
 * revient). Un culte saisi hors connexion est garde ici (localStorage,
 * propre a cet appareil, jamais envoye a personne d'autre) puis transmis au
 * serveur des que possible.
 *
 * Volontairement PAS la Background Sync API (mal supportee hors Chrome,
 * notamment sur iPhone, qui equipe une bonne partie des utilisateurs vises) :
 * la synchronisation se declenche simplement des que l'appareil redevient en
 * ligne pendant que l'appli est ouverte (voir OfflineIndicator.vue), ce qui
 * couvre le cas reel vise (le secretaire rouvre l'appli une fois de retour
 * en zone couverte). Limite connue, acceptee pour cette premiere pierre : si
 * la coupure dure plus longtemps que la session de connexion, il faudra se
 * reconnecter avant que l'envoi automatique reparte.
 */
const STORAGE_KEY = 'oikonema-cultes-en-attente';

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

export function queueCulte(orgUnitId, data) {
    const queue = readQueue();

    queue.push({
        localId: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        orgUnitId,
        data,
        createdAt: new Date().toISOString(),
    });

    writeQueue(queue);
}

export function countQueuedCultes() {
    return readQueue().length;
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta ? meta.getAttribute('content') : '';
}

/**
 * Envoie au serveur chaque culte en attente, un par un et dans l'ordre
 * (jamais en parallele). Un culte accepte par le serveur est retire de la
 * file. Au premier probleme (coupure reseau en cours d'envoi, ou refus du
 * serveur), on s'arrete immediatement plutot que de continuer dans le
 * desordre : l'element reste dans la file, rien n'est perdu, on reessaiera
 * au complet la prochaine fois.
 */
export async function syncQueuedCultes() {
    let queue = readQueue();
    let envoyes = 0;

    for (const item of [...queue]) {
        try {
            const response = await fetch(`/org-units/${item.orgUnitId}/cultes`, {
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
