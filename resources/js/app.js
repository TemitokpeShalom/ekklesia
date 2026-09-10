import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import AssistantWidget from './Components/Assistant/AssistantWidget.vue';

createInertiaApp({
    title: (title) => (title ? `${title} · Ekklesia` : 'Ekklesia'),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Assistant IA (2026-09-10) : monte comme frere de la page Inertia,
        // pas a l'interieur d'AppLayout.vue - plusieurs ecrans plus anciens
        // ne passent pas encore par ce layout (voir son commentaire d'en-
        // tete), et l'assistant doit rester visible sur TOUTES les pages
        // authentifiees des la connexion. AssistantWidget.vue se cache lui-
        // meme tant qu'aucun utilisateur n'est connecte.
        createApp({ render: () => h('div', [h(App, props), h(AssistantWidget)]) })
            .use(plugin)
            .mount(el);
    },
});
