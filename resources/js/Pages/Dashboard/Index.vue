<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-08) : meme structure, memes props, memes regles
 * d'affichage que la version precedente (rien de fonctionnel ne change) --
 * seule l'habillage passe du parchemin clair a la coquille sombre en verre
 * depoli d'AppLayout. Deux modules ajoutes ici pour le point 17 de la
 * feuille de route : "Canal de signalement" et "Assistant" (recherche
 * dans le manuel), tous deux ouverts a quiconque voit ce noeud (pas de
 * requiresRoot), comme decrit dans le point.
 */
defineProps({
    orgUnit: Object,
    children: Array,
    activeAffectations: Array,
    canAccessLibrary: Boolean,
    canManageAccess: Boolean,
    canTransform: Boolean,
})

// Un module par carte : badge en degrade (une nuance vers une nuance plus
// sombre de la meme couleur, jamais un arc-en-ciel), icone, courte
// description. Le bleu (slateblue) et le vert (forest) ne servent qu'a
// distinguer deux ou trois modules du reste de la famille vin/or/brun.
const modules = [
    {
        key: 'membres', label: 'Membres', desc: 'Fidèles, familles, fiches simples',
        path: 'membres', badge: 'from-forest to-forest/70', glow: 'hover:shadow-glow-forest',
        icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m5-5.13a4 4 0 100-8 4 4 0 000 8zm6 3a4 4 0 10-3-6.65',
    },
    {
        key: 'trombinoscope', label: 'Trombinoscope', desc: 'Grille de photos, imprimable',
        path: 'trombinoscope', badge: 'from-gold to-gold-dark', glow: 'hover:shadow-glow-gold',
        icon: 'M3 9a2 2 0 012-2h.5l1-1.5h11l1 1.5H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z',
        icon2: 'M12 13m-3.2 0a3.2 3.2 0 106.4 0a3.2 3.2 0 10-6.4 0',
    },
    {
        key: 'documents', label: 'Documents', desc: 'Affiche, calendrier, trombinoscope',
        path: 'documents', badge: 'from-sanctuary to-sanctuary-dark', glow: 'hover:shadow-glow-sanctuary',
        icon: 'M4 4h16v16H4V4zM4 9h16M9 4v16',
    },
    {
        key: 'cultes', label: 'Cultes et effectifs', desc: 'Présences, thème, versets',
        path: 'cultes', badge: 'from-sanctuary to-sanctuary-dark', glow: 'hover:shadow-glow-sanctuary',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        key: 'sacrements', label: 'Sacrements', desc: 'Baptêmes et mariages, par membre',
        path: 'sacrements', badge: 'from-forest to-forest/70', glow: 'hover:shadow-glow-forest',
        icon: 'M12 21a9 9 0 100-18 9 9 0 000 18zm0-13.5v6l3.75 2.25',
    },
    {
        key: 'parcours', label: 'Parcours de disciple', desc: 'Étapes de croissance, par membre',
        path: 'parcours', badge: 'from-slateblue to-slateblue/70', glow: 'hover:shadow-glow-slateblue',
        icon: 'M4.26 10.15a60 60 0 00-.49 6.34A48.6 48.6 0 0012 20.9a48.6 48.6 0 008.23-4.41 60 60 0 00-.49-6.34M4.03 9.33A50.7 50.7 0 0112 3.5a50.7 50.7 0 0110.4 5.84',
        icon2: 'M4.03 9.33A50.7 50.7 0 0112 13.49a50.7 50.7 0 007.74-3.34',
    },
    {
        key: 'bibliotheque', label: 'Bibliothèque ministérielle', desc: 'Prédications, thèmes, résumés',
        path: 'bibliotheque', badge: 'from-slateblue to-slateblue/70', glow: 'hover:shadow-glow-slateblue', requiresRoot: true, requiresLibrary: true,
        icon: 'M12 6.25C10.5 5 8.5 4.5 6 4.5c-1 0-2 .1-3 .4v13.6c1-.3 2-.4 3-.4 2.5 0 4.5.5 6 1.75m0-13.6c1.5-1.25 3.5-1.75 6-1.75 1 0 2 .1 3 .4v13.6c-1-.3-2-.4-3-.4-2.5 0-4.5.5-6 1.75m0-13.6v13.6',
    },
    {
        key: 'finances', label: 'Finances', desc: 'Dîmes, offrandes, dépenses',
        path: 'finances', badge: 'from-gold to-gold-dark', glow: 'hover:shadow-glow-gold',
        icon: 'M12 7.5v9M14.5 9.75c0-1.24-1.12-2.25-2.5-2.25s-2.5.9-2.5 2c0 3 5 1.5 5 4.5 0 1.1-1.12 2-2.5 2s-2.5-1.01-2.5-2.25',
        circle: true,
    },
    {
        key: 'inventaire', label: 'Inventaire des biens', desc: 'Immobilier, mobilier, état',
        path: 'inventaire', badge: 'from-coffee to-coffee-dark', glow: 'hover:shadow-glow-gold',
        icon: 'M3.5 8.5L12 4l8.5 4.5-8.5 4.5-8.5-4.5z',
        icon2: 'M3.5 8.5v7L12 20l8.5-4.5v-7M12 13v7',
    },
    {
        key: 'annonces', label: 'Annonces', desc: 'Diffusion du haut vers le bas',
        path: 'annonces', badge: 'from-sanctuary to-sanctuary-dark', glow: 'hover:shadow-glow-sanctuary',
        icon: 'M3 11l18-7-6 18-3.5-7.5L3 11z',
        icon2: 'M11.5 14.5L21 4',
    },
    {
        key: 'rapport', label: "Rapport d'activités", desc: 'Vie de l\'église, chaque mois',
        path: 'rapport-activites', badge: 'from-slateblue to-slateblue/70', glow: 'hover:shadow-glow-slateblue',
        icon: 'M4 20V10m6 10V4m6 16v-7',
    },
    {
        key: 'signalements', label: 'Canal de signalement', desc: 'Remonter une préoccupation',
        path: 'signalements', badge: 'from-coffee to-coffee-dark', glow: 'hover:shadow-glow-gold',
        icon: 'M12 9v3.75m0 3.75h.007v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
]
</script>

<template>
    <AppLayout :org-unit="orgUnit">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Tableau de bord
                </p>
                <h1 class="font-serif text-3xl sm:text-4xl text-white mt-2">{{ orgUnit.name }}</h1>
            </div>
        </template>

        <div class="space-y-14">
            <section v-if="activeAffectations.length" class="animate-[fadeInUp_0.5s_ease-out_both]">
                <h2 class="text-xs font-semibold text-white/40 uppercase tracking-widest mb-3">Mes affectations actives</h2>
                <div class="flex flex-wrap gap-2">
                    <span v-for="a in activeAffectations" :key="a.id"
                        class="inline-flex items-center gap-1.5 glass-panel-light rounded-full pl-3 pr-4 py-1.5 text-sm">
                        <span class="font-semibold text-gold-soft">{{ a.role.label }}</span>
                        <span class="text-white/50">· {{ a.org_unit.name }}</span>
                    </span>
                </div>
            </section>

            <section>
                <h2 class="font-serif text-2xl text-white mb-1">Modules</h2>
                <p class="text-sm text-white/55 mb-6">Tout ce qui se gère au quotidien pour {{ orgUnit.name }}.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <template v-for="(m, i) in modules" :key="m.key">
                        <a v-if="!m.requiresRoot || (orgUnit.level_rank === 0 && (!m.requiresLibrary || canAccessLibrary))"
                            :href="`/org-units/${orgUnit.id}/${m.path}`"
                            :class="m.glow"
                            class="group relative glass-panel rounded-3xl p-6 hover:border-white/20 hover:-translate-y-1 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                            :style="{ animationDelay: `${i * 60}ms` }">
                            <span :class="m.badge" class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <circle v-if="m.circle" cx="12" cy="12" r="8.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="m.icon" />
                                    <path v-if="m.icon2" stroke-linecap="round" stroke-linejoin="round" :d="m.icon2" />
                                </svg>
                            </span>
                            <p class="font-semibold text-white text-[15px]">{{ m.label }}</p>
                            <p class="text-xs text-white/55 mt-1">{{ m.desc }}</p>
                            <svg class="absolute top-6 right-6 w-4 h-4 text-white/40 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    </template>

                    <Link href="/assistant"
                        class="group relative glass-panel rounded-3xl p-6 hover:border-white/20 hover:shadow-glow-slateblue hover:-translate-y-1 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                        style="animation-delay: 600ms;">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br from-slateblue to-slateblue/70 text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3a6 6 0 00-3.5 10.9V16a1 1 0 001 1h5a1 1 0 001-1v-2.1A6 6 0 0012 3z" /></svg>
                        </span>
                        <p class="font-semibold text-white text-[15px]">Assistant</p>
                        <p class="text-xs text-white/55 mt-1">Recherche rapide dans le manuel</p>
                    </Link>

                    <Link href="/aide"
                        class="group relative glass-panel rounded-3xl p-6 hover:border-white/20 hover:shadow-glow-gold hover:-translate-y-1 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                        style="animation-delay: 660ms;">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br from-coffee to-coffee-dark text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.5 9a2.5 2.5 0 115 0c0 1.5-2.5 1.8-2.5 3.5M12 17h.01M4 5h16a1 1 0 011 1v12a1 1 0 01-1 1H8l-4 3V6a1 1 0 011-1z" /></svg>
                        </span>
                        <p class="font-semibold text-white text-[15px]">Manuel d'utilisation</p>
                        <p class="text-xs text-white/55 mt-1">Aide écran par écran</p>
                    </Link>
                </div>
            </section>

            <section v-if="canManageAccess">
                <h2 class="font-serif text-2xl text-white mb-1">Gouvernance des accès</h2>
                <p class="text-sm text-white/55 mb-6">Inviter, révoquer, rattacher de nouvelles entités.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <a :href="`/org-units/${orgUnit.id}/acces`"
                        class="group glass-panel rounded-3xl p-6 hover:border-gold/25 hover:shadow-glow-gold hover:-translate-y-1 transition-all duration-300">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br from-gold to-gold-dark text-night shadow-md group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 11-4 0 2 2 0 014 0zM6 21v-2a4 4 0 014-4h1m9-3l-3 3m0 0l-3-3m3 3V4" /></svg>
                        </span>
                        <p class="font-semibold text-white text-[15px]">Gérer les accès</p>
                        <p class="text-xs text-white/55 mt-1">Lister, révoquer, réaffecter</p>
                    </a>
                    <a :href="`/org-units/${orgUnit.id}/inviter`"
                        class="group glass-panel rounded-3xl p-6 hover:border-gold/25 hover:shadow-glow-gold hover:-translate-y-1 transition-all duration-300">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br from-gold to-gold-dark text-night shadow-md group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0" /></svg>
                        </span>
                        <p class="font-semibold text-white text-[15px]">Inviter un titulaire</p>
                        <p class="text-xs text-white/55 mt-1">Un nouveau responsable</p>
                    </a>
                    <a :href="`/org-units/${orgUnit.id}/code-de-rattachement`"
                        class="group glass-panel rounded-3xl p-6 hover:border-gold/25 hover:shadow-glow-gold hover:-translate-y-1 transition-all duration-300">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br from-gold to-gold-dark text-night shadow-md group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a3 3 0 11-6 0 3 3 0 016 0zM4 20a5 5 0 0110 0M14 12l6 6m0 0v-4m0 4h-4" /></svg>
                        </span>
                        <p class="font-semibold text-white text-[15px]">Code de rattachement</p>
                        <p class="text-xs text-white/55 mt-1">Créer une entité rattachée</p>
                    </a>
                    <a v-if="orgUnit.level_rank === 0" :href="`/org-units/${orgUnit.id}/titres-honorifiques`"
                        class="group glass-panel rounded-3xl p-6 hover:border-gold/25 hover:shadow-glow-gold hover:-translate-y-1 transition-all duration-300">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br from-gold to-gold-dark text-night shadow-md group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4l1.8 4.2L18 10l-4.2 1.8L12 16l-1.8-4.2L6 10l4.2-1.8L12 4zM5 18l.8 1.8L7.5 20l-1.7.8L5 22.5l-.8-1.7L2.5 20l1.7-.2L5 18z" /></svg>
                        </span>
                        <p class="font-semibold text-white text-[15px]">Titres honorifiques</p>
                        <p class="text-xs text-white/55 mt-1">Liste utilisée sur les fiches membres</p>
                    </a>
                    <a v-if="orgUnit.level_rank === 0" :href="`/org-units/${orgUnit.id}/abonnement`"
                        class="group glass-panel rounded-3xl p-6 hover:border-gold/25 hover:shadow-glow-gold hover:-translate-y-1 transition-all duration-300">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br from-gold to-gold-dark text-night shadow-md group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z" /></svg>
                        </span>
                        <p class="font-semibold text-white text-[15px]">Abonnement</p>
                        <p class="text-xs text-white/55 mt-1">Offre, essai, facturation</p>
                    </a>
                </div>
            </section>

            <section v-if="canTransform">
                <h2 class="font-serif text-2xl text-white mb-1">Structure organisationnelle</h2>
                <p class="text-sm text-white/55 mb-6">Faire évoluer cette entité sans perdre son historique.</p>
                <a :href="`/org-units/${orgUnit.id}/transformation`"
                    class="group inline-flex items-center glass-panel rounded-3xl p-6 hover:border-white/20 hover:shadow-glow-sanctuary hover:-translate-y-1 transition-all duration-300 max-w-sm w-full">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mr-4 shrink-0 bg-gradient-to-br from-sanctuary to-sanctuary-dark text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4.5 15a8 8 0 0013.9 3.4M19.5 9A8 8 0 005.6 5.6" /></svg>
                    </span>
                    <span>
                        <p class="font-semibold text-white text-[15px]">Transformer cette entité</p>
                        <p class="text-xs text-white/55 mt-1">Renommer, promouvoir, rattacher</p>
                    </span>
                </a>
            </section>

            <section>
                <h2 class="font-serif text-2xl text-white mb-1">
                    {{ children.length ? 'Entités directement rattachées' : "Aucune entité rattachée pour l'instant" }}
                </h2>
                <p v-if="children.length" class="text-sm text-white/55 mb-6">Cliquer pour ouvrir son propre tableau de bord.</p>
                <div v-if="children.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <a v-for="child in children" :key="child.id" :href="`/org-units/${child.id}`"
                        class="group glass-panel rounded-3xl p-6 hover:border-white/20 hover:-translate-y-1 transition-all duration-300">
                        <span class="text-xs uppercase tracking-widest text-gold-soft/90 font-semibold block mb-1">{{ child.level_label }}</span>
                        <p class="font-semibold text-white text-[15px]">{{ child.name }}</p>
                    </a>
                </div>
                <p v-else class="text-sm text-white/55">
                    Les prochaines entités rattachées à {{ orgUnit.name }} apparaîtront ici.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
