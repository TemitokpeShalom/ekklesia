// Service worker Oikonema.
//
// Historique : 2026-09-12 installabilite ; 2026-09-13 premiere brique hors
// connexion (page de secours) ; 2026-11-01 (v3) PAGES DISPONIBLES HORS
// CONNEXION.
//
// Ce que fait la v3 :
//  1. Fichiers de l'application (scripts, styles, polices, images) : gardes
//     sur l'appareil, l'application s'ouvre donc sans reseau.
//  2. Pages de travail (tableau de bord, listes et formulaires de saisie de
//     chaque module) : la DERNIERE version vue est gardee sur l'appareil.
//     Reseau d'abord (donnees toujours a jour quand la connexion marche) ;
//     si le reseau echoue ou tarde plus de quelques secondes, la version
//     gardee est servie. Une page jamais vue est preparee a l'avance par
//     l'application (voir resources/js/offlineCache.js).
//  3. Les saisies faites hors connexion partent au retour du reseau (files
//     d'attente, voir resources/js/offline*Queue.js).
//
// Confidentialite (important, les donnees ne doivent jamais se melanger) :
//  - Seules les pages d'une LISTE BLANCHE precise sont gardees (voir
//    PAGES_GARDEES) : jamais le profil, l'abonnement, les acces, l'espace
//    technique, les paiements, ni les telechargements de fichiers.
//  - Une page n'est gardee que si le serveur l'a renvoyee normalement a cette
//    adresse (pas une redirection vers la connexion).
//  - Les messages temporaires (flash : codes de rattachement, liens
//    d'invitation, confirmations) sont retires avant d'enregistrer.
//  - Le cache de donnees est efface a la deconnexion et des qu'une autre
//    personne se connecte sur le meme appareil (voir offlineCache.js).
//  - Le cache reste propre a l'appareil : rien n'est envoye a personne.

// v3 : nouveau nom de cache d'application, pour renouveler l'existant.
const RUNTIME_CACHE = 'oikonema-shell-v3';
// Cache des pages de travail. NE PAS le supprimer dans "activate" : il garde
// les donnees de la personne connectee entre deux versions du service worker.
const DATA_CACHE = 'oikonema-data-v1';

const OFFLINE_URL = '/hors-connexion.html';
const MEMBERS_OFFLINE_URL = '/membres-hors-connexion.html';
const PRECACHE_URLS = [OFFLINE_URL, MEMBERS_OFFLINE_URL, '/images/oikonema-icon.png'];

const MARQUEUR_PAGE = '__OIKONEMA_PAGE__';
const CLE_GABARIT = '/__sw/gabarit';
const CLE_ACCUEIL = '/__sw/accueil';
const DELAI_NAVIGATION_MS = 6000;
const DELAI_APPLICATION_MS = 8000;
const MAX_PAGES_GARDEES = 220;

const UUID = '[0-9a-fA-F-]{36}';
const MODULES_LISTE = 'membres|trombinoscope|documents|cultes|parcours|sacrements|equipes|bibliotheque|finances|finances-rapport|rapport-activites|inventaire|inventaire-rapport|annonces|signalements';
const PAGES_GARDEES = [
    /^\/$/,
    new RegExp(`^/org-units/${UUID}$`),
    new RegExp(`^/org-units/${UUID}/(${MODULES_LISTE})$`),
    new RegExp(`^/org-units/${UUID}/(membres|cultes|parcours|sacrements|finances|inventaire)/nouveau$`),
    new RegExp(`^/org-units/${UUID}/(equipes/nouvelle|annonces/nouvelle)$`),
    new RegExp(`^/org-units/${UUID}/documents/(calendrier|rapports|archives)$`),
    new RegExp(`^/org-units/${UUID}/equipes/${UUID}/membres$`),
];
const TABLEAU_DE_BORD = new RegExp(`^/org-units/${UUID}$`);

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(RUNTIME_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .catch(() => {
                // Echec silencieux (ex. hors connexion des la toute premiere
                // visite) : jamais un echec d'installation du service worker.
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

// ---------------------------------------------------------------------------
// Outils
// ---------------------------------------------------------------------------

function pageGardee(pathname) {
    return PAGES_GARDEES.some((regle) => regle.test(pathname));
}

function echapperAttribut(texte) {
    return texte
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function decoderAttribut(texte) {
    return texte
        .replace(/&quot;/g, '"')
        .replace(/&#0?39;/g, "'")
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&amp;/g, '&');
}

/** Retire de la page tout message temporaire avant de la garder. */
function nettoyerPage(page) {
    if (page && page.props) {
        page.props.flash = { success: null, error: null, plain_code: null, invitation_link: null };
        page.props.errors = {};
    }

    return page;
}

function avecDelai(promesse, ms) {
    return new Promise((resolve, reject) => {
        const minuteur = setTimeout(() => reject(new Error('delai')), ms);
        promesse.then(
            (valeur) => { clearTimeout(minuteur); resolve(valeur); },
            (erreur) => { clearTimeout(minuteur); reject(erreur); }
        );
    });
}

function cleDonnees(pathname, search) {
    return `/__sw/page?u=${encodeURIComponent(pathname + (search || ''))}`;
}

async function lireJson(cache, cle) {
    const reponse = await cache.match(cle);

    return reponse ? reponse.json() : null;
}

async function limiterTaille(cache) {
    const cles = await cache.keys();

    if (cles.length > MAX_PAGES_GARDEES) {
        // Les plus anciennes entrees d'abord (ordre d'insertion).
        await Promise.all(cles.slice(0, cles.length - MAX_PAGES_GARDEES + 40).map((cle) => cache.delete(cle)));
    }
}

/** Range une page (objet Inertia) apres l'avoir nettoyee. */
async function garderPage(url, page) {
    const cache = await caches.open(DATA_CACHE);
    const nettoyee = nettoyerPage(page);
    const cle = cleDonnees(url.pathname, url.search);

    await cache.put(cle, new Response(JSON.stringify({ gardeLe: Date.now(), page: nettoyee }), {
        headers: { 'Content-Type': 'application/json' },
    }));

    if (TABLEAU_DE_BORD.test(url.pathname)) {
        await cache.put(CLE_ACCUEIL, new Response(JSON.stringify({ cle }), {
            headers: { 'Content-Type': 'application/json' },
        }));
    }

    await limiterTaille(cache);
}

/** Analyse une reponse HTML du serveur : garde la page ET le gabarit. */
async function garderDepuisHtml(reponse) {
    const url = new URL(reponse.url);

    if (!reponse.ok || !pageGardee(url.pathname)) {
        return;
    }

    const html = await reponse.text();
    const trouve = html.match(/data-page="([^"]*)"/);

    if (!trouve) {
        return;
    }

    const page = JSON.parse(decoderAttribut(trouve[1]));

    // Garde-fou : jamais une page de connexion ou d'erreur sous une adresse de travail.
    if (!page || typeof page.component !== 'string' || /^(Auth|Error)/.test(page.component)) {
        return;
    }

    await garderPage(url, page);

    const cache = await caches.open(DATA_CACHE);
    await cache.put(CLE_GABARIT, new Response(html.replace(trouve[0], () => `data-page="${MARQUEUR_PAGE}"`), {
        headers: { 'Content-Type': 'text/html; charset=utf-8' },
    }));
}

/** Analyse une reponse "application" (JSON Inertia). */
async function garderDepuisJson(reponse) {
    const url = new URL(reponse.url);

    if (!reponse.ok || !pageGardee(url.pathname) || reponse.headers.get('X-Inertia') !== 'true') {
        return;
    }

    const page = await reponse.json();

    if (!page || typeof page.component !== 'string' || /^(Auth|Error)/.test(page.component)) {
        return;
    }

    await garderPage(url, page);
}

/** Reconstruit une page HTML a partir de la version gardee. */
async function pageHtmlGardee(url) {
    const cache = await caches.open(DATA_CACHE);
    const gabarit = await cache.match(CLE_GABARIT);

    if (!gabarit) {
        return null;
    }

    let cle = cleDonnees(url.pathname, url.search);

    if (url.pathname === '/') {
        const accueil = await lireJson(cache, CLE_ACCUEIL);
        if (!accueil) {
            return null;
        }
        cle = accueil.cle;
    }

    const gardee = await lireJson(cache, cle);

    if (!gardee) {
        return null;
    }

    const html = (await gabarit.text()).replace(MARQUEUR_PAGE, () => echapperAttribut(JSON.stringify(gardee.page)));

    return new Response(html, {
        status: 200,
        headers: { 'Content-Type': 'text/html; charset=utf-8', 'X-Oikonema-Hors-Connexion': '1' },
    });
}

/** Reponse "application" (JSON Inertia) a partir de la version gardee. */
async function pageJsonGardee(url) {
    const cache = await caches.open(DATA_CACHE);
    const gardee = await lireJson(cache, cleDonnees(url.pathname, url.search));

    if (!gardee) {
        return null;
    }

    return new Response(JSON.stringify(gardee.page), {
        status: 200,
        headers: {
            'Content-Type': 'application/json',
            'X-Inertia': 'true',
            Vary: 'X-Inertia',
            'X-Oikonema-Hors-Connexion': '1',
        },
    });
}

// ---------------------------------------------------------------------------
// Interception des requetes
// ---------------------------------------------------------------------------

async function repondrePageHtml(event) {
    const requete = event.request;
    const url = new URL(requete.url);
    let enregistrement = Promise.resolve();

    const reseau = fetch(requete).then((reponse) => {
        // Uniquement une vraie page (pas une redirection opaque ni une erreur).
        if (reponse.type === 'basic' || reponse.type === 'default') {
            enregistrement = garderDepuisHtml(reponse.clone()).catch(() => {});
        }

        return reponse;
    });

    event.waitUntil(reseau.then(() => enregistrement, () => {}));

    try {
        return await avecDelai(reseau, DELAI_NAVIGATION_MS);
    } catch (e) {
        const gardee = await pageHtmlGardee(url);

        if (gardee) {
            return gardee;
        }

        // Pas de version gardee : si le reseau repond finalement, on l'attend.
        try {
            return await reseau;
        } catch (e2) {
            if (requete.mode === 'navigate') {
                const secours = (await caches.match(requete)) || (await caches.match(OFFLINE_URL));

                return secours || new Response('Hors connexion', { status: 503 });
            }

            return new Response('Hors connexion', { status: 503 });
        }
    }
}

async function repondreApplication(event) {
    const requete = event.request;
    const url = new URL(requete.url);
    const partielle = requete.headers.has('X-Inertia-Partial-Data');
    let enregistrement = Promise.resolve();

    const reseau = fetch(requete).then((reponse) => {
        if (!partielle) {
            enregistrement = garderDepuisJson(reponse.clone()).catch(() => {});
        }

        return reponse;
    });

    event.waitUntil(reseau.then(() => enregistrement, () => {}));

    try {
        return await avecDelai(reseau, DELAI_APPLICATION_MS);
    } catch (e) {
        const gardee = await pageJsonGardee(url);

        if (gardee) {
            return gardee;
        }

        try {
            return await reseau;
        } catch (e2) {
            // Page jamais gardee : l'application affichera ce message.
            const secours = await caches.match(OFFLINE_URL);

            return secours
                ? new Response(await secours.text(), { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } })
                : new Response('Page non disponible hors connexion', { status: 503 });
        }
    }
}

function demandeHtml(requete) {
    const accepte = requete.headers.get('accept') || '';

    return requete.mode === 'navigate' || (accepte.includes('text/html') && !requete.headers.has('X-Inertia'));
}

self.addEventListener('fetch', (event) => {
    const requete = event.request;
    const url = new URL(requete.url);

    // Uniquement les lectures (GET), sur ce meme site - jamais les ecritures
    // (creer/modifier/supprimer) ni un autre domaine (passerelle de paiement...).
    if (requete.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Requete de l'application elle-meme (changement de page sans rechargement).
    if (requete.headers.get('X-Inertia') === 'true') {
        if (pageGardee(url.pathname)) {
            event.respondWith(repondreApplication(event));
        }

        return;
    }

    // Ouverture / rechargement d'une page.
    if (demandeHtml(requete)) {
        if (pageGardee(url.pathname)) {
            event.respondWith(repondrePageHtml(event));

            return;
        }

        if (requete.mode === 'navigate') {
            // Page non gardee : reseau d'abord ; sinon la page statique gardee
            // si elle existe (ex. membres-hors-connexion.html), puis la page
            // de secours generique.
            event.respondWith(
                fetch(requete).catch(() =>
                    caches.match(requete).then((gardee) => gardee || caches.match(OFFLINE_URL))
                )
            );
        }

        return;
    }

    // La liste des fichiers compiles doit toujours venir du reseau (elle sert
    // a detecter une nouvelle version deployee) : jamais servie depuis le cache.
    if (url.pathname.endsWith('/manifest.json')) {
        return;
    }

    // Fichiers compiles (JS/CSS de public/build) : un changement de contenu
    // change toujours le nom du fichier (voir Vite). Cache d'abord, mis a
    // jour en arriere-plan.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.open(RUNTIME_CACHE).then((cache) => cache.match(requete).then((gardee) => {
                const reseau = fetch(requete)
                    .then((reponse) => {
                        if (reponse.ok) {
                            cache.put(requete, reponse.clone());
                        }

                        return reponse;
                    })
                    .catch(() => gardee);

                return gardee || reseau;
            }))
        );

        return;
    }

    // Polices, images et icones de l'application : cache d'abord.
    if (/^\/(fonts|images|icons)\//.test(url.pathname)) {
        event.respondWith(
            caches.open(RUNTIME_CACHE).then((cache) => cache.match(requete).then((gardee) => {
                if (gardee) {
                    return gardee;
                }

                return fetch(requete).then((reponse) => {
                    if (reponse.ok) {
                        cache.put(requete, reponse.clone());
                    }

                    return reponse;
                });
            }))
        );
    }
});
