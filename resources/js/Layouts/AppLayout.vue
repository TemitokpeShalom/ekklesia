<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()
const flashSuccess = computed(() => page.props.flash?.success)
const flashError = computed(() => page.props.flash?.error)
const user = computed(() => page.props.auth?.user)
const userInitial = computed(() => (user.value?.name?.trim()?.charAt(0) || '?').toUpperCase())

const profileMenuOpen = ref(false)

/**
 * Coquille partagee v4 "Constellation" (2026-09-10).
 *
 * Retour du ministere sur la v3 "Vitrail" (fond sombre) : trop noir/vert,
 * modules mal hierarchises, aucun vrai "profil" utilisateur, options de
 * gouvernance affichees comme des tuiles ordinaires au milieu du tableau
 * de bord. Cette version :
 *  - repasse sur un fond blanc net (voir .app-shell dans app.css et les
 *    tokens "paper"/"graphite" de tailwind.config.js) plutot que de
 *    reprendre le parchemin v1, deja juge "trop classique" ;
 *  - retire les halos animes et le filigrane de rosace (registre plus
 *    sobre, "plateforme institutionnelle" plutot que decoratif) ; seul
 *    vestige de la marque : le lisere vin -> or tout en haut de l'ecran ;
 *  - remplace les boutons "Rattachement"/"Aide"/"Se deconnecter" eparpilles
 *    par un unique menu "profil" (avatar + nom), qui donne aussi acces a
 *    la nouvelle page /profil (voir ProfileController) ;
 *  - le slot "actions" (deja existant) est desormais l'endroit ou une page
 *    comme Dashboard/Index.vue place son propre menu "Gouvernance", pour
 *    que les options d'administration ne soient plus des tuiles du tableau
 *    mais un menu que l'on ouvre au besoin.
 * Migration en une fois (contrairement a v1->v3 qui s'est faite ecran par
 * ecran) : toutes les pages qui utilisent cette coquille ont ete
 * reconverties en meme temps (voir scratchpad/convert_theme.py), pour
 * eviter un entre-deux ou une moitie de l'app serait claire et l'autre
 * sombre. Les ecrans PUBLICS (connexion, accueil, inscription d'un
 * ministere, invitations, code de rattachement pour un visiteur non
 * connecte) restent volontairement en dehors de cette coquille et gardent
 * l'habillage sombre "Vitrail" : une vitrine de marque a l'entree, un
 * outil de travail sobre a l'interieur.
 */
defineProps({
    orgUnit: { type: Object, default: null },
    // Lien "retour" optionnel (ex: retour au tableau de bord depuis un module).
    backHref: { type: String, default: null },
    backLabel: { type: String, default: 'Retour au tableau de bord' },
})
</script>

<template>
    <div class="app-shell min-h-screen bg-paper text-graphite relative overflow-x-hidden">
        <!-- Seul vestige decoratif de l'identite "Vitrail" : un lisere vin -> or, discret, en haut de chaque ecran. -->
        <div class="h-1 bg-gradient-to-r from-sanctuary via-sanctuary-light to-gold" aria-hidden="true"></div>

        <header class="relative z-10 sticky top-0 border-b border-graphite/10 glass-panel">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
                <Link :href="orgUnit ? `/org-units/${orgUnit.id}` : '/'" class="flex items-center gap-3 min-w-0 group">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-gradient-to-br from-gold to-gold-dark flex items-center justify-center text-night font-serif font-bold text-[15px] shadow-glow-gold group-hover:scale-105 transition-transform duration-300">O</span>
                    <span class="min-w-0" v-if="orgUnit">
                        <span class="block text-[10.5px] uppercase tracking-widest text-sanctuary/80 font-semibold truncate">{{ orgUnit.level_label }}</span>
                        <span class="block font-serif text-graphite text-[15px] leading-tight truncate">{{ orgUnit.name }}</span>
                    </span>
                    <span class="font-serif text-graphite text-lg" v-else>Oikonema</span>
                </Link>
                <nav class="flex items-center gap-2 shrink-0">
                    <slot name="actions" />
                    <Link v-if="backHref" :href="backHref" class="text-sm text-graphite/70 hover:text-graphite bg-graphite/5 hover:bg-graphite/10 border border-graphite/10 rounded-full px-4 py-2 transition-colors">{{ backLabel }}</Link>

                    <!--
                        Menu "profil" (2026-09-10) : remplace les trois
                        boutons eparpilles Rattachement/Aide/Se deconnecter
                        par un seul point d'entree, avec en plus l'acces a
                        un vrai profil utilisateur (page /profil) - demande
                        explicite du ministere ("il y a des pages ou on a
                        le profil de l'utilisateur... comment un bon
                        profil"). Ferme au clic exterieur via l'overlay
                        transparent ci-dessous (pas de dependance externe).
                    -->
                    <div class="relative">
                        <button type="button" @click="profileMenuOpen = !profileMenuOpen"
                            class="flex items-center gap-2 pl-1.5 pr-3 py-1.5 rounded-full border border-graphite/10 bg-graphite/[0.03] hover:bg-graphite/[0.06] transition-colors">
                            <span class="w-7 h-7 rounded-full bg-gradient-to-br from-sanctuary to-sanctuary-dark text-white flex items-center justify-center text-xs font-semibold shrink-0">{{ userInitial }}</span>
                            <span class="hidden sm:block text-sm text-graphite/80 max-w-[9rem] truncate">{{ user?.name || 'Mon compte' }}</span>
                            <svg class="w-3.5 h-3.5 text-graphite/50 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <button v-if="profileMenuOpen" type="button" tabindex="-1" aria-hidden="true"
                            @click="profileMenuOpen = false" class="fixed inset-0 z-10 cursor-default"></button>

                        <div v-if="profileMenuOpen"
                            class="absolute right-0 mt-2 w-56 z-20 rounded-2xl border border-graphite/10 bg-white shadow-card-hover py-2 animate-[fadeInUp_0.15s_ease-out_both]">
                            <p class="px-4 py-1.5 text-xs text-graphite/60 truncate">{{ user?.email }}</p>
                            <Link href="/profil" class="block px-4 py-2 text-sm text-graphite/80 hover:bg-graphite/5" @click="profileMenuOpen = false">Mon profil</Link>
                            <Link href="/rattachement" class="block px-4 py-2 text-sm text-graphite/80 hover:bg-graphite/5" @click="profileMenuOpen = false">Rattachement</Link>
                            <Link href="/aide" class="block px-4 py-2 text-sm text-graphite/80 hover:bg-graphite/5" @click="profileMenuOpen = false">Aide</Link>
                            <div class="my-1.5 border-t border-graphite/10"></div>
                            <Link href="/deconnexion" method="post" as="button" class="w-full text-left block px-4 py-2 text-sm text-sanctuary hover:bg-sanctuary/5">Se déconnecter</Link>
                        </div>
                    </div>
                </nav>
            </div>
            <div v-if="$slots.title" class="max-w-6xl mx-auto px-6 pb-6 -mt-1">
                <slot name="title" />
            </div>
        </header>

        <!--
            Bandeau de message ephemere (succes/erreur) - centralise ici
            (audit du 2026-09-09) pour que toute redirection avec message
            le montre, sans dupliquer ce bandeau dans chaque page.
        -->
        <div v-if="flashSuccess || flashError" class="relative z-10 max-w-6xl mx-auto px-6 pt-4">
            <p v-if="flashSuccess" class="text-sm text-forest bg-forest/10 border border-forest/30 rounded-xl px-4 py-2.5">{{ flashSuccess }}</p>
            <p v-if="flashError" class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5">{{ flashError }}</p>
        </div>

        <main class="relative z-10 max-w-6xl mx-auto px-6 py-10 md:py-14">
            <slot />
        </main>

        <footer class="relative z-10 max-w-6xl mx-auto px-6 pb-10 pt-4">
            <p class="text-xs text-graphite/55 text-center">Oikonema, plateforme de gestion de ministère</p>
        </footer>
    </div>
</template>
