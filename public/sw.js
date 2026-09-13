// Service worker (2026-09-12 : installabilite ; 2026-09-13 : premiere
// brique du chantier hors connexion, voir la proposition ecrite du 13/09).
//
// Ce fichier ne met encore en cache AUCUNE donnee du ministere (membres,
// cultes...) - seulement des fichiers statiques sans risque : les scripts/
// styles compiles (noms uniques par contenu, jamais perimes) et une page de
// secours hors-ligne (hors-connexion.html, statique, sans donnee). Toute
// page qui affiche des informations reelles continue d'exiger le reseau,
// pour la meme raison qu'avant (RLS par ministere - mettre une page de
// donnees en cache ici risquerait de montrer une information perimee ou
// d'un autre ministere). Le stockage local propre a Membres et Cultes
// arrivera dans une prochaine livraison, a part.

// v2 (2026-09-13) : nouvelle page precachee (membres-hors-connexion.html) -
// nom de cache change pour forcer son telechargement sur les appareils qui
// avaient deja installe la version precedente du service worker.
const RUNTIME_CACHE = 'oikonema-shell-v2';
const OFFLINE_URL = '/hors-connexion.html';
// 2026-09-13 (deuxieme pierre) : membres-hors-connexion.html est une page
// statique independante (lecture seule, alimentee par localStorage cote
// Members/Index.vue) - precachee ici pour rester joignable hors connexion,
// au meme titre que la page de secours generique.
const MEMBERS_OFFLINE_URL = '/membres-hors-connexion.html';
const PRECACHE_URLS = [OFFLINE_URL, MEMBERS_OFFLINE_URL, '/images/oikonema-icon.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(RUNTIME_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .catch(() => {
                // Echec silencieux (ex. hors connexion des la toute premiere
                // visite) : au pire, pas de page de secours disponible plus
                // tard, jamais un echec d'installation du service worker
                // lui-meme.
            })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith('oikonema-shell-') && key !== RUNTIME_CACHE)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Uniquement les lectures (GET), sur ce meme site - jamais les
    // ecritures (creer/modifier/supprimer) ni un autre domaine (passerelle
    // de paiement...), qui doivent echouer normalement hors connexion
    // plutot que d'etre interceptees ici.
    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
        return;
    }

    // Navigation (ouverture ou rechargement d'une page) : reseau d'abord ;
    // si ca echoue, on sert la page demandee elle-meme si elle fait partie
    // des pages statiques precachees (ex. membres-hors-connexion.html), et
    // seulement sinon la page de secours generique - jamais de contenu ou
    // de donnees mises en cache ici, voir l'explication en tete de fichier.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() =>
                caches.match(request).then((cached) => cached || caches.match(OFFLINE_URL))
            )
        );
        return;
    }

    // Fichiers compiles (JS/CSS de public/build) : sans risque a garder en
    // cache, un changement de contenu changeant toujours aussi le nom du
    // fichier (voir Vite) - reseau tente en priorite pour rester a jour
    // (nouvelle mise a jour deployee), le cache ne sert que si le reseau
    // echoue.
    if (request.url.includes('/build/')) {
        event.respondWith(
            caches.open(RUNTIME_CACHE).then((cache) => cache.match(request).then((cached) => {
                const network = fetch(request)
                    .then((response) => {
                        if (response.ok) {
                            cache.put(request, response.clone());
                        }

                        return response;
                    })
                    .catch(() => cached);

                return cached || network;
            }))
        );
    }
});
