<script setup>
import { Link } from '@inertiajs/vue3'

/**
 * Page d'accueil publique (v5 "vitrine", 2026-09-10) - voir WelcomeController
 * pour le contexte. Reprend le meme decor "vitrail" que Login.vue pour que le
 * tout premier ecran de l'application soit visuellement coherent avec celui
 * qui suit juste apres (clic sur "Se connecter").
 *
 * Enrichie a la demande du ministere (chantier OIKONEMA) pour presenter
 * concretement ce que la plateforme administre, sur le modele d'une page
 * d'accueil de logiciel (carte de modules) plutot que la simple carte de
 * connexion d'avant - sans dupliquer les vraies donnees du dashboard : les
 * memes 6 modules-cles (icones et couleurs identiques a Dashboard/Index.vue)
 * servent ici de vitrine, pas de raccourci fonctionnel (aucun lien direct
 * vers un module, puisque visiteur non connecte).
 */
const modules = [
    {
        label: 'Membres', desc: 'Fidèles, familles, fiches simples',
        badge: 'from-forest to-forest/70',
        icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m5-5.13a4 4 0 100-8 4 4 0 000 8zm6 3a4 4 0 10-3-6.65',
    },
    {
        label: 'Finances', desc: 'Dîmes, offrandes, dépenses',
        badge: 'from-gold to-gold-dark',
        icon: 'M12 7.5v9M14.5 9.75c0-1.24-1.12-2.25-2.5-2.25s-2.5.9-2.5 2c0 3 5 1.5 5 4.5 0 1.1-1.12 2-2.5 2s-2.5-1.01-2.5-2.25',
    },
    {
        label: 'Cultes et effectifs', desc: 'Présences, thème, versets',
        badge: 'from-sanctuary to-sanctuary-dark',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        label: 'Sacrements', desc: 'Baptêmes et mariages, par membre',
        badge: 'from-forest to-forest/70',
        icon: 'M12 21a9 9 0 100-18 9 9 0 000 18zm0-13.5v6l3.75 2.25',
    },
    {
        label: 'Équipes et bénévolat', desc: 'Accueil, louange, enfants, technique...',
        badge: 'from-coffee to-coffee-dark',
        icon: 'M18 18.72a9.09 9.09 0 003.74-.48 3 3 0 00-4.68-2.72M12 12.75a5.99 5.99 0 015.06 2.77m0 0a3 3 0 014.68 2.72 8.99 8.99 0 01-3.74.48M12 12.75a5.99 5.99 0 00-5.06 2.77m0 0a3 3 0 00-4.68 2.72 8.99 8.99 0 003.74.48m5.99-3.2A5.97 5.97 0 006 18.72M15 6.75a3 3 0 11-6 0 3 3 0 016 0z',
    },
    {
        label: 'Annonces', desc: 'Diffusion du haut vers le bas',
        badge: 'from-sanctuary to-sanctuary-dark',
        icon: 'M3 11l18-7-6 18-3.5-7.5L3 11z',
        icon2: 'M11.5 14.5L21 4',
    },
]

const atouts = [
    'Isolation stricte entre ministères : les données de chacun restent les siennes',
    'Historique conservé à chaque renommage, transformation ou changement de responsable',
    'Accessible depuis un ordinateur ou un téléphone, sans installation',
]
</script>

<template>
    <div class="min-h-screen relative overflow-x-hidden bg-night">
        <div class="absolute -top-24 -right-16 w-[28rem] h-[28rem] bg-sanctuary/26 rounded-full blur-[100px]" style="animation: driftGlow 14s ease-in-out infinite;" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-16 w-[28rem] h-[28rem] bg-gold/18 rounded-full blur-[100px]" style="animation: driftGlow 18s ease-in-out infinite reverse;" aria-hidden="true"></div>
        <div class="absolute top-1/3 right-1/4 w-80 h-80 bg-forest/22 rounded-full blur-[100px]" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-vitrail" aria-hidden="true"></div>

        <svg class="absolute left-1/2 top-0 -translate-x-1/2 w-[42rem] h-[42rem] opacity-[0.07] pointer-events-none hidden sm:block"
            viewBox="0 0 200 200" fill="none" stroke="#f1e4c8" stroke-width="0.7" aria-hidden="true"
            style="animation: shimmerPulse 9s ease-in-out infinite;">
            <circle cx="100" cy="100" r="78" />
            <circle cx="100" cy="100" r="60" />
            <circle cx="100" cy="100" r="16" />
            <g v-for="n in 16" :key="n" :transform="`rotate(${n * 22.5} 100 100)`">
                <line x1="100" y1="22" x2="100" y2="100" />
            </g>
        </svg>

        <div class="relative px-4 pt-16 pb-20 md:pt-24">
            <!-- Carte d'accueil (inchangee dans son role : premier point de contact) -->
            <div class="mx-auto w-full max-w-md glass-panel p-8 rounded-3xl shadow-2xl shadow-black/40 border-t-2 border-t-gold/70 animate-[fadeInUp_0.6s_ease-out_both] text-center">
                <img src="/images/oikonema-logo.png" alt="OIKONEMA" class="w-40 sm:w-44 mx-auto mb-3 rounded-2xl bg-white p-2 shadow-glow-gold" />
                <p class="text-xs uppercase tracking-widest text-gold-soft/90 font-semibold mb-5">Bienvenue</p>
                <p class="text-sm text-white/60 mb-8 -mt-2">Administrer tout ce qui vous est confié — membres, finances, vie pastorale, patrimoine — en un seul endroit.</p>

                <div class="space-y-3">
                    <Link href="/connexion"
                        class="w-full inline-flex items-center justify-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-3 font-semibold shadow-lg shadow-gold/20">
                        Se connecter à mon espace de travail
                    </Link>
                    <Link href="/ministeres/nouveau"
                        class="w-full inline-flex items-center justify-center gap-1.5 bg-white/5 hover:bg-white/10 border border-white/15 transition-colors text-white rounded-xl py-3 font-semibold">
                        Créer un nouveau ministère
                    </Link>
                </div>

                <p class="text-center text-sm text-white/50 mt-7">
                    Vous avez reçu un code de rattachement ?
                    <a href="/rattachement" class="text-gold-soft hover:underline font-medium">Rejoindre votre église</a>
                </p>
            </div>

            <!-- Vitrine des modules (2026-09-10) : demande explicite du ministere,
                 sur le modele des "cartes de modules" d'une page d'accueil de
                 logiciel. Purement presentatif (visiteur non connecte) : memes
                 icones/couleurs que Dashboard/Index.vue, aucun lien direct. -->
            <div class="relative mx-auto w-full max-w-5xl mt-20 animate-[fadeInUp_0.7s_ease-out_both]">
                <div class="text-center mb-10">
                    <p class="text-xs uppercase tracking-widest text-gold-soft/90 font-semibold mb-2">Ce que la plateforme administre</p>
                    <h2 class="font-serif text-2xl sm:text-3xl text-white">Une seule administration, pour toute la vie du ministère</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div v-for="m in modules" :key="m.label"
                        class="glass-panel rounded-3xl p-6 border border-white/10">
                        <span :class="['inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br text-white shadow-md', m.badge]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="m.icon" />
                                <path v-if="m.icon2" stroke-linecap="round" stroke-linejoin="round" :d="m.icon2" />
                            </svg>
                        </span>
                        <p class="font-semibold text-white text-[15px]">{{ m.label }}</p>
                        <p class="text-xs text-white/55 mt-1">{{ m.desc }}</p>
                    </div>
                </div>

                <div class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div v-for="a in atouts" :key="a" class="flex items-start gap-2.5 text-sm text-white/60">
                        <svg class="w-4 h-4 mt-0.5 shrink-0 text-gold-soft" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>{{ a }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
