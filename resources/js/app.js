import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import AssistantWidget from './Components/Assistant/AssistantWidget.vue';
import InstallPrompt from './Components/InstallPrompt.vue';

createInertiaApp({
    title: (title) => (title ? `${title} · Oikonema` : 'Oikonema'),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Assistant IA (2026-09-10) : monte comme frere de la page Inertia,
        // pas a l'interieur d'AppLayout.vue - plusieurs ecrans plus anciens
        // ne passent pas encore par ce layout (voir son commentaire d'en-
        // tete), et l'assistant doit rester visible sur TOUTES les pages
        // authentifiees des la connexion. AssistantWidget.vue se cache lui-
        // meme tant qu'aucun utilisateur n'est connecte.
        //
        // Bandeau d'installation (2026-09-12, voir InstallPrompt.vue) : meme
        // principe de montage global, mais SANS se cacher pour une personne
        // non connectee - le cas vise en premier est justement quelqu'un qui
        // suit un lien d'inscription et n'a donc pas encore de compte.
        createApp({ render: () => h('div', [h(App, props), h(AssistantWidget), h(InstallPrompt)]) })
            .use(plugin)
            .mount(el);
    },
});
