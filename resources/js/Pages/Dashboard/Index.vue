<script setup>
import { Link } from '@inertiajs/vue3'

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
        path: 'membres', badge: 'from-forest to-forest/70',
        icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m5-5.13a4 4 0 100-8 4 4 0 000 8zm6 3a4 4 0 10-3-6.65',
    },
    {
        key: 'trombinoscope', label: 'Trombinoscope', desc: 'Grille de photos, imprimable',
        path: 'trombinoscope', badge: 'from-gold to-gold-dark',
        icon: 'M3 9a2 2 0 012-2h.5l1-1.5h11l1 1.5H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z',
        icon2: 'M12 13m-3.2 0a3.2 3.2 0 106.4 0a3.2 3.2 0 10-6.4 0',
    },
    {
        key: 'documents', label: 'Documents', desc: 'Affiche, calendrier, trombinoscope',
        path: 'documents', badge: 'from-sanctuary to-sanctuary-dark',
        icon: 'M4 4h16v16H4V4zM4 9h16M9 4v16',
    },
    {
        key: 'cultes', label: 'Cultes et effectifs', desc: 'Présences, thème, versets',
        path: 'cultes', badge: 'from-sanctuary to-sanctuary-dark',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        key: 'bibliotheque', label: 'Bibliothèque ministérielle', desc: 'Prédications, thèmes, résumés',
        path: 'bibliotheque', badge: 'from-slateblue to-slateblue/70', requiresRoot: true, requiresLibrary: true,
        icon: 'M12 6.25C10.5 5 8.5 4.5 6 4.5c-1 0-2 .1-3 .4v13.6c1-.3 2-.4 3-.4 2.5 0 4.5.5 6 1.75m0-13.6c1.5-1.25 3.5-1.75 6-1.75 1 0 2 .1 3 .4v13.6c-1-.3-2-.4-3-.4-2.5 0-4.5.5-6 1.75m0-13.6v13.6',
    },
    {
        key: 'finances', label: 'Finances', desc: 'Dîmes, offrandes, dépenses',
        path: 'finances', badge: 'from-gold to-gold-dark',
        icon: 'M12 7.5v9M14.5 9.75c0-1.24-1.12-2.25-2.5-2.25s-2.5.9-2.5 2c0 3 5 1.5 5 4.5 0 1.1-1.12 2-2.5 2s-2.5-1.01-2.5-2.25',
        circle: true,
    },
    {
        key: 'inventaire', label: 'Inventaire des biens', desc: 'Immobilier, mobilier, état',
        path: 'inventaire', badge: 'from-coffee to-coffee-dark',
        icon: 'M3.5 8.5L12 4l8.5 4.5-8.5 4.5-8.5-4.5z',
        icon2: 'M3.5 8.5v7L12 20l8.5-4.5v-7M12 13v7',
    },
    {
        key: 'annonces', label: 'Annonces', desc: 'Diffusion du haut vers le bas',
        path: 'annonces', badge: 'from-sanctuary to-sanctuary-dark',
        icon: 'M3 11l18-7-6 18-3.5-7.5L3 11z',
        icon2: 'M11.5 14.5L21 4',
    },
    {
        key: 'rapport', label: "Rapport d'activités", desc: 'Vie de l\'église, chaque mois',
        path: 'rapport-activites', badge: 'from-slateblue to-slateblue/70',
        icon: 'M4 20V10m6 10V4m6 16v-7',
    },
]
</script>

<template>
    <div class="min-h-screen bg-parchment">
        <header class="relative overflow-hidden bg-gradient-to-br from-sanctuary-dark via-sanctuary to-coffee-dark">
            <div class="absolute -top-28 -right-10 w-[26rem] h-[26rem] bg-gold/25 rounded-full blur-3xl" style="animation: driftGlow 16s ease-in-out infinite;" aria-hidden="true"></div>
            <div class="absolute -bottom-40 left-1/4 w-96 h-96 bg-slateblue/20 rounded-full blur-3xl" style="animation: driftGlow 20s ease-in-out infinite reverse;" aria-hidden="true"></div>
            <div class="absolute top-0 left-1/2 w-72 h-72 bg-forest/15 rounded-full blur-3xl" aria-hidden="true"></div>

            <svg class="absolute right-[-40px] top-1/2 -translate-y-1/2 w-80 h-80 md:w-[24rem] md:h-[24rem] opacity-[0.07] pointer-events-none hidden sm:block"
                viewBox="0 0 200 200" fill="none" stroke="white" stroke-width="1.3" aria-hidden="true">
                <path d="M20 150 C60 135,90 135,100 145 C110 135,140 135,180 150 L180 160 C140 145,110 145,100 155 C90 145,60 145,20 160 Z" />
                <path d="M100 145 L100 155" />
                <path d="M30 145 C55 133,80 133,95 141" />
                <path d="M30 152 C55 140,80 140,95 148" />
                <path d="M170 145 C145 133,120 133,105 141" />
                <path d="M170 152 C145 140,120 140,105 148" />
                <path d="M100 38 L100 92" />
                <path d="M76 58 L124 58" />
                <circle cx="100" cy="95" r="46" stroke-opacity="0.5" />
            </svg>

            <div class="relative max-w-6xl mx-auto px-6 py-12 md:py-16 flex items-start justify-between gap-6">
                <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                    <p class="text-xs uppercase tracking-widest text-gold-soft/90 font-semibold flex items-center gap-2">
                        <span class="inline-block w-6 h-px bg-gold-soft/70"></span>
                        {{ orgUnit.level_label }}
                    </p>
                    <h1 class="font-serif text-4xl sm:text-5xl text-white mt-2">{{ orgUnit.name }}</h1>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link href="/aide" class="text-sm text-white/85 hover:text-white bg-white/10 hover:bg-white/20 backdrop-blur border border-white/10 rounded-full px-4 py-2 transition">Aide</Link>
                    <form method="post" action="/deconnexion">
                        <button class="text-sm text-white/85 hover:text-white bg-white/10 hover:bg-white/20 backdrop-blur border border-white/10 rounded-full px-4 py-2 transition">Se déconnecter</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-6 py-12 md:py-14 space-y-14">
            <section v-if="activeAffectations.length" class="animate-[fadeInUp_0.5s_ease-out_both]">
                <h2 class="text-xs font-semibold text-coffee-light uppercase tracking-widest mb-3">Mes affectations actives</h2>
                <div class="flex flex-wrap gap-2">
                    <span v-for="a in activeAffectations" :key="a.id"
                        class="inline-flex items-center gap-1.5 bg-white border border-gold-soft rounded-full pl-3 pr-4 py-1.5 text-sm text-ink shadow-sm">
                        <span class="font-semibold text-sanctuary">{{ a.role.label }}</span>
                        <span class="text-coffee-light">· {{ a.org_unit.name }}</span>
                    </span>
                </div>
            </section>

            <section>
                <h2 class="font-serif text-2xl text-ink mb-1">Modules</h2>
                <p class="text-sm text-coffee-light mb-6">Tout ce qui se gère au quotidien pour {{ orgUnit.name }}.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <template v-for="(m, i) in modules" :key="m.key">
                        <a v-if="!m.requiresRoot || (orgUnit.level_rank === 0 && (!m.requiresLibrary || canAccessLibrary))"
                            :href="`/org-units/${orgUnit.id}/${m.path}`"
                            class="group relative bg-white border border-coffee/10 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:shadow-coffee/10 hover:-translate-y-1 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                            :style="{ animationDelay: `${i * 60}ms` }">
                            <span :class="m.badge" class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <circle v-if="m.circle" cx="12" cy="12" r="8.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="m.icon" />
                                    <path v-if="m.icon2" stroke-linecap="round" stroke-linejoin="round" :d="m.icon2" />
                                </svg>
                            </span>
                            <p class="font-semibold text-ink text-[15px]">{{ m.label }}</p>
                            <p class="text-xs text-coffee-light mt-1">{{ m.desc }}</p>
                            <svg class="absolute top-6 right-6 w-4 h-4 text-coffee-light opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    </template>

                    <Link href="/aide"
                        class="group relative bg-white border border-coffee/10 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:shadow-coffee/10 hover:-translate-y-1 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                        style="animation-delay: 480ms;">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br from-coffee to-coffee-dark text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.5 9a2.5 2.5 0 115 0c0 1.5-2.5 1.8-2.5 3.5M12 17h.01M4 5h16a1 1 0 011 1v12a1 1 0 01-1 1H8l-4 3V6a1 1 0 011-1z" /></svg>
                        </span>
                        <p class="font-semibold text-ink text-[15px]">Manuel d'utilisation</p>
                        <p class="text-xs text-coffee-light mt-1">Aide écran par écran</p>
                    </Link>
                </div>
            </section>

            <section v-if="canManageAccess">
                <h2 class="font-serif text-2xl text-ink mb-1">Gouvernance des accès</h2>
                <p class="text-sm text-coffee-light mb-6">Inviter, révoquer, rattacher de nouvelles entités.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <a :href="`/org-units/${orgUnit.id}/acces`"
                        class="group bg-white border border-gold-soft rounded-3xl p-6 shadow-sm hover:shadow-xl hover:shadow-gold/10 hover:-translate-y-1 transition-all duration-300">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br from-gold to-gold-dark text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 11-4 0 2 2 0 014 0zM6 21v-2a4 4 0 014-4h1m9-3l-3 3m0 0l-3-3m3 3V4" /></svg>
                        </span>
                        <p class="font-semibold text-ink text-[15px]">Gérer les accès</p>
                        <p class="text-xs text-coffee-light mt-1">Lister, révoquer, réaffecter</p>
                    </a>
                    <a :href="`/org-units/${orgUnit.id}/inviter`"
                        class="group bg-white border border-gold-soft rounded-3xl p-6 shadow-sm hover:shadow-xl hover:shadow-gold/10 hover:-translate-y-1 transition-all duration-300">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br from-gold to-gold-dark text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0" /></svg>
                        </span>
                        <p class="font-semibold text-ink text-[15px]">Inviter un titulaire</p>
                        <p class="text-xs text-coffee-light mt-1">Un nouveau responsable</p>
                    </a>
                    <a :href="`/org-units/${orgUnit.id}/code-de-rattachement`"
                        class="group bg-white border border-gold-soft rounded-3xl p-6 shadow-sm hover:shadow-xl hover:shadow-gold/10 hover:-translate-y-1 transition-all duration-300">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br from-gold to-gold-dark text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a3 3 0 11-6 0 3 3 0 016 0zM4 20a5 5 0 0110 0M14 12l6 6m0 0v-4m0 4h-4" /></svg>
                        </span>
                        <p class="font-semibold text-ink text-[15px]">Code de rattachement</p>
                        <p class="text-xs text-coffee-light mt-1">Créer une entité rattachée</p>
                    </a>
                    <a v-if="orgUnit.level_rank === 0" :href="`/org-units/${orgUnit.id}/titres-honorifiques`"
                        class="group bg-white border border-gold-soft rounded-3xl p-6 shadow-sm hover:shadow-xl hover:shadow-gold/10 hover:-translate-y-1 transition-all duration-300">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br from-gold to-gold-dark text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4l1.8 4.2L18 10l-4.2 1.8L12 16l-1.8-4.2L6 10l4.2-1.8L12 4zM5 18l.8 1.8L7.5 20l-1.7.8L5 22.5l-.8-1.7L2.5 20l1.7-.2L5 18z" /></svg>
                        </span>
                        <p class="font-semibold text-ink text-[15px]">Titres honorifiques</p>
                        <p class="text-xs text-coffee-light mt-1">Liste utilisée sur les fiches membres</p>
                    </a>
                </div>
            </section>

            <section v-if="canTransform">
                <h2 class="font-serif text-2xl text-ink mb-1">Structure organisationnelle</h2>
                <p class="text-sm text-coffee-light mb-6">Faire évoluer cette entité sans perdre son historique.</p>
                <a :href="`/org-units/${orgUnit.id}/transformation`"
                    class="group inline-flex items-center bg-white border border-coffee/10 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:shadow-coffee/10 hover:-translate-y-1 transition-all duration-300 max-w-sm w-full">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mr-4 shrink-0 bg-gradient-to-br from-sanctuary to-sanctuary-dark text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4.5 15a8 8 0 0013.9 3.4M19.5 9A8 8 0 005.6 5.6" /></svg>
                    </span>
                    <span>
                        <p class="font-semibold text-ink text-[15px]">Transformer cette entité</p>
                        <p class="text-xs text-coffee-light mt-1">Renommer, promouvoir, rattacher</p>
                    </span>
                </a>
            </section>

            <section>
                <h2 class="font-serif text-2xl text-ink mb-1">
                    {{ children.length ? 'Entités directement rattachées' : "Aucune entité rattachée pour l'instant" }}
                </h2>
                <p v-if="children.length" class="text-sm text-coffee-light mb-6">Cliquer pour ouvrir son propre tableau de bord.</p>
                <div v-if="children.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <a v-for="child in children" :key="child.id" :href="`/org-units/${child.id}`"
                        class="group bg-white border border-coffee/10 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:shadow-coffee/10 hover:-translate-y-1 transition-all duration-300">
                        <span class="text-xs uppercase tracking-widest text-gold-dark font-semibold block mb-1">{{ child.level_label }}</span>
                        <p class="font-semibold text-ink text-[15px]">{{ child.name }}</p>
                    </a>
                </div>
                <p v-else class="text-sm text-coffee-light">
                    Les prochaines entités rattachées à {{ orgUnit.name }} apparaîtront ici.
            </section>
        </main>
    </div>
</template>
