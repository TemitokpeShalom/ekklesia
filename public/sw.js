// Service worker minimal (retour du ministere, 2026-09-12 : rendre
// l'application "installable" sur telephone). Sa seule raison d'etre est
// de satisfaire le critere d'installabilite d'Android/Chrome (voir
// InstallPrompt.vue) - volontairement SANS mode hors ligne : l'application
// a besoin du serveur pour fonctionner (donnees d'un ministere, RLS...),
// mettre des pages en cache ici risquerait de servir une donnee perimee ou
// une page d'un autre ministere depuis le cache. Rien n'est mis en cache ;
// chaque requete part normalement au reseau.
self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', () => {
    // Gestionnaire vide : sa seule presence suffit au critere
    // d'installabilite, sans jamais intercepter ni modifier une reponse.
});
