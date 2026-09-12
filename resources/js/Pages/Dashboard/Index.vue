<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MinistryLetterhead from '@/Components/MinistryLetterhead.vue'

/**
 * v4 "Constellation" (2026-09-10) : demande du ministere de revoir
 * l'architecture de cet ecran, pas seulement ses couleurs -- "les options
 * de gestion de gouvernance... doit s'afficher sous forme de menu quelque
 * part ou on clique. Et puis les autres fonctions cles peuvent rester sur
 * le tableau." Les sections "Gouvernance des acces" et "Structure
 * organisationnelle" (avant : des tuiles au meme niveau que les modules du
 * quotidien) sont retirees du corps de la page et deviennent le menu
 * deroulant "Gouvernance" ci-dessous, place dans l'en-tete (slot #actions
 * d'AppLayout) - visible seulement si l'utilisateur a le droit d'y toucher
 * (canManageAccess/canTransform, deja fournis par DashboardController,
 * aucun changement cote back). Le tableau de bord lui-meme ne garde que
 * les "fonctions cles" : les modules operationnels du quotidien et les
 * entites rattachees.
 *
 * v3 "Vitrail" (2026-09-08) : module "Canal de signalement" ajoute pour le
 * point 17 de la feuille de route, ouvert a quiconque voit ce noeud (pas
 * de requiresRoot), comme decrit dans le point.
 */
const props = defineProps({
    orgUnit: Object,
    ministry: Object,
    children: Array,
    activeAffectations: Array,
    canAccessLibrary: Boolean,
    canManageAccess: Boolean,
    canTransform: Boolean,
    canCreateChild: Boolean,
    subscription: Object,
})

const governanceMenuOpen = ref(false)

// Creation directe d'une entite enfant a ce noeud (retour du ministere,
// 2026-09-12, point 03) - remplace le mecanisme par code de rattachement :
// on reste sur cette page, un formulaire s'ouvre, on nomme la nouvelle
// entite et on choisit son niveau (forcement en dessous de celui-ci), elle
// se rattache automatiquement ici, sans code a generer ni a transmettre.
const LEVEL_NAMES = ['Ministère', 'Continent', 'Pays', 'Région', 'District', 'Église locale', 'Cellule']
const showCreateForm = ref(false)
const availableChildRanks = computed(() =>
    LEVEL_NAMES
        .map((label, rank) => ({ rank, label }))
        .filter(({ rank }) => rank > props.orgUnit.level_rank)
)

const page = usePage()
const createdOrgUnitId = computed(() => page.props.flash?.created_org_unit_id)
const createdOrgUnitName = computed(() => page.props.flash?.created_org_unit_name)

const createForm = useForm({
    name: '',
    level_rank: '',
})

// Point 28/29 (retour du ministere, 2026-09-12 : "ce n'est pas seulement
// eglise locale qu'il faut regarder... tout est decompte en meme temps,
// quel que soit le niveau") - rappel du quota d'abonnement (nombre
// d'entites autorisees, TOUS niveaux confondus) visible des l'ouverture de
// ce formulaire, quel que soit le niveau choisi - plus seulement pour
// "Eglise locale" (Ministry::assertCanCreateOrgUnit s'applique a tous).
const orgUnitsRemainingLabel = computed(() => {
    const limit = props.subscription?.orgUnitsLimit
    if (limit === null || limit === undefined) return null
    const remaining = Math.max(0, limit - (props.subscription?.orgUnitsCount ?? 0))
    return `${remaining} sur ${limit} entité(s) restante(s) (tous niveaux confondus) sur l'offre « ${props.subscription?.planName ?? 'actuelle'} ».`
})

function submitCreate() {
    createForm.post(`/org-units/${props.orgUnit.id}/entites`, {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset()
            showCreateForm.value = false
        },
    })
}

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
        // Corrige le 2026-09-12 (retour du ministere : "garder le
        // trombinoscope comme un module a part entiere hors du module
        // document") : sorti du hub Documents, devient un module du
        // tableau de bord comme les autres. Route inchangee.
        key: 'trombinoscope', label: 'Trombinoscope', desc: 'Grille de photos des membres actifs, imprimable',
        path: 'trombinoscope', badge: 'from-gold to-gold-dark', glow: 'hover:shadow-glow-gold',
        icon: 'M3 9a2 2 0 012-2h.5l1-1.5h11l1 1.5H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z',
        icon2: 'M12 13m-3.2 0a3.2 3.2 0 106.4 0a3.2 3.2 0 10-6.4 0',
    },
    {
        key: 'documents', label: 'Documents', desc: "Rapports validés, affiche, calendrier, archives",
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
        key: 'equipes', label: 'Équipes et bénévolat', desc: 'Accueil, louange, enfants, technique...',
        path: 'equipes', badge: 'from-coffee to-coffee-dark', glow: 'hover:shadow-glow-gold',
        icon: 'M18 18.72a9.09 9.09 0 003.74-.48 3 3 0 00-4.68-2.72M12 12.75a5.99 5.99 0 015.06 2.77m0 0a3 3 0 014.68 2.72 8.99 8.99 0 01-3.74.48M12 12.75a5.99 5.99 0 00-5.06 2.77m0 0a3 3 0 00-4.68 2.72 8.99 8.99 0 003.74.48m5.99-3.2A5.97 5.97 0 006 18.72M15 6.75a3 3 0 11-6 0 3 3 0 016 0z',
    },
    {
        // Corrige le 2026-09-12 (retour du ministere : "ce module soit
        // visible partout dans le ministere... au niveau de la region... du
        // district... des eglises locales... des cellules de priere") :
        // n'est plus reserve au niveau Ministere (requiresRoot retire) -
        // visible a tout niveau, uniquement pour les comptes qui prechent
        // (requiresLibrary, deja calcule par DashboardController a tout
        // niveau : voir hasPreachingAffectation()).
        key: 'bibliotheque', label: 'Bibliothèque ministérielle', desc: 'Prédications, thèmes, résumés',
        path: 'bibliotheque', badge: 'from-slateblue to-slateblue/70', glow: 'hover:shadow-glow-slateblue', requiresLibrary: true,
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
    <AppLayout :org-unit="orgUnit" :back-href="orgUnit.parent_id ? `/org-units/${orgUnit.parent_id}` : null" back-label="Retour">
        <template #actions>
            <div v-if="canManageAccess || canTransform" class="relative">
                <button type="button" @click="governanceMenuOpen = !governanceMenuOpen"
                    class="flex items-center gap-1.5 text-sm text-graphite/70 hover:text-graphite bg-graphite/5 hover:bg-graphite/10 border border-graphite/10 rounded-full px-4 py-2 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.96 11.96 0 013.598 6 11.96 11.96 0 001.5 12.253c0 4.798 3.036 9.036 7.65 10.632a11.96 11.96 0 004.7 0C18.464 21.29 21.5 17.05 21.5 12.253 21.5 9.68 20.807 7.284 19.5 5.25a11.96 11.96 0 01-8.5-.036z" /></svg>
                    <span class="hidden sm:inline">Gouvernance</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </button>

                <button v-if="governanceMenuOpen" type="button" tabindex="-1" aria-hidden="true"
                    @click="governanceMenuOpen = false" class="fixed inset-0 z-10 cursor-default"></button>

                <div v-if="governanceMenuOpen"
                    class="absolute right-0 mt-2 w-80 z-20 rounded-2xl border border-graphite/10 bg-white shadow-card-hover py-2 animate-[fadeInUp_0.15s_ease-out_both]">
                    <template v-if="canManageAccess">
                        <p class="px-4 pt-1.5 pb-1.5 text-[11px] uppercase tracking-widest text-graphite/60 font-semibold">Gestion des accès</p>
                        <a :href="`/org-units/${orgUnit.id}/acces`" class="block px-4 py-2 hover:bg-graphite/5" @click="governanceMenuOpen = false">
                            <p class="text-sm font-medium text-graphite">Gérer les accès</p>
                            <p class="text-xs text-graphite/60">Lister, révoquer, réaffecter</p>
                        </a>
                        <a :href="`/org-units/${orgUnit.id}/inviter`" class="block px-4 py-2 hover:bg-graphite/5" @click="governanceMenuOpen = false">
                            <p class="text-sm font-medium text-graphite">Inviter un titulaire</p>
                            <p class="text-xs text-graphite/60">Un nouveau responsable</p>
                        </a>
                        <!--
                            Corrige le 2026-09-12 (retour du ministere : "il
                            faut le laisser dans le menu deroulant de gerer la
                            gouvernance... pas ramener ca comme un module
                            affiche en meme temps") : remplace l'ancien "Code
                            de rattachement" ICI MEME, au meme endroit dans ce
                            menu - pas de bouton permanent affiche ailleurs sur
                            la page. Un clic ferme le menu et ouvre le
                            formulaire (toujours sur ce meme tableau de bord,
                            juste plus bas, section "Entites rattachees") -
                            plus aucun code a generer ni a transmettre.
                        -->
                        <a v-if="canCreateChild" href="#" @click.prevent="showCreateForm = true; governanceMenuOpen = false"
                            class="block px-4 py-2 hover:bg-graphite/5">
                            <p class="text-sm font-medium text-graphite">Créer une entité rattachée</p>
                            <p class="text-xs text-graphite/60">Directement à ce nœud, sans code</p>
                        </a>
                        <template v-if="orgUnit.level_rank === 0">
                            <a :href="`/org-units/${orgUnit.id}/informations-ministere`" class="block px-4 py-2 hover:bg-graphite/5" @click="governanceMenuOpen = false">
                                <p class="text-sm font-medium text-graphite">Informations du ministère</p>
                                <p class="text-xs text-graphite/60">Sigle, siège, coordonnées, n° d'autorisation, logo</p>
                            </a>
                            <a :href="`/org-units/${orgUnit.id}/titres-honorifiques`" class="block px-4 py-2 hover:bg-graphite/5" @click="governanceMenuOpen = false">
                                <p class="text-sm font-medium text-graphite">Titres honorifiques</p>
                                <p class="text-xs text-graphite/60">Liste utilisée sur les fiches membres</p>
                            </a>
                            <a :href="`/org-units/${orgUnit.id}/abonnement`" class="block px-4 py-2 hover:bg-graphite/5" @click="governanceMenuOpen = false">
                                <p class="text-sm font-medium text-graphite">Abonnement</p>
                                <p class="text-xs text-graphite/60">Offre, essai, facturation</p>
                            </a>
                        </template>
                    </template>
                    <template v-if="canTransform">
                        <div v-if="canManageAccess" class="my-1.5 border-t border-graphite/10"></div>
                        <p class="px-4 pt-1.5 pb-1.5 text-[11px] uppercase tracking-widest text-graphite/60 font-semibold">Structure organisationnelle</p>
                        <a :href="`/org-units/${orgUnit.id}/transformation`" class="block px-4 py-2 hover:bg-graphite/5" @click="governanceMenuOpen = false">
                            <p class="text-sm font-medium text-graphite">Transformer cette entité</p>
                            <p class="text-xs text-graphite/60">Renommer, promouvoir, rattacher</p>
                        </a>
                    </template>
                </div>
            </div>
        </template>

        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Tableau de bord
                </p>
                <h1 class="font-serif text-6xl sm:text-7xl font-bold text-graphite mt-2">{{ orgUnit.name }}</h1>
            </div>
        </template>

        <div class="space-y-14">
            <MinistryLetterhead :ministry="ministry" />

            <section v-if="activeAffectations.length" class="animate-[fadeInUp_0.5s_ease-out_both]">
                <h2 class="text-2xl font-bold text-graphite/62 uppercase tracking-widest mb-3">Mes affectations actives</h2>
                <div class="flex flex-wrap gap-2">
                    <span v-for="a in activeAffectations" :key="a.id"
                        class="inline-flex items-center gap-1.5 glass-panel-light rounded-full pl-3 pr-4 py-1.5 text-sm">
                        <span class="font-semibold text-sanctuary">{{ a.role.label }}</span>
                        <span class="text-graphite/68">· {{ a.org_unit.name }}</span>
                    </span>
                </div>
            </section>

            <section>
                <h2 class="font-serif text-5xl font-bold text-graphite mb-1">Modules</h2>
                <p class="text-sm text-graphite/70 mb-6">Tout ce qui se gère au quotidien pour {{ orgUnit.name }}.</p>
                <!--
                    Corrige le 2026-09-12 (retour du ministere : "l'espace
                    qu'occupe chaque module est un peu grand... les boutons
                    sont un peu larges", "la taille des ecritures... un peu
                    augmenter") : une 4e colonne apparait sur tres grand ecran
                    (xl) pour que chaque carte reste compacte au lieu de
                    s'etirer sur toute la largeur disponible, le padding
                    interne et l'icone sont legerement reduits, et le texte
                    (libelle + description) est legerement agrandi. Meme
                    presentation en cartes qu'avant (deja repensee plusieurs
                    fois avec le ministere) : changement de gabarit, pas de
                    refonte.
                -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <template v-for="(m, i) in modules" :key="m.key">
                        <a v-if="!m.requiresLibrary || canAccessLibrary"
                            :href="`/org-units/${orgUnit.id}/${m.path}`"
                            :class="m.glow"
                            class="group relative glass-panel rounded-3xl p-5 hover:border-graphite/20 hover:-translate-y-1 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                            :style="{ animationDelay: `${i * 60}ms` }">
                            <span :class="m.badge" class="inline-flex items-center justify-center w-10 h-10 rounded-2xl mb-3 bg-gradient-to-br text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <circle v-if="m.circle" cx="12" cy="12" r="8.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="m.icon" />
                                    <path v-if="m.icon2" stroke-linecap="round" stroke-linejoin="round" :d="m.icon2" />
                                </svg>
                            </span>
                            <p class="font-semibold text-graphite text-base">{{ m.label }}</p>
                            <p class="text-[13px] text-graphite/70 mt-1">{{ m.desc }}</p>
                            <svg class="absolute top-5 right-5 w-4 h-4 text-graphite/62 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    </template>
                </div>
                <!--
                    Pas de tuile "Aide"/"Assistant" ici : le lien "Aide" du
                    menu profil (AppLayout.vue, present sur cet ecran comme
                    sur tous les autres) est desormais le seul acces au
                    manuel, recherche comprise - voir HelpController.
                -->
            </section>

            <section>
                <h2 class="font-serif text-5xl font-bold text-graphite mb-1">
                    {{ children.length ? 'Entités directement rattachées' : "Aucune entité rattachée pour l'instant" }}
                </h2>
                <p v-if="children.length" class="text-sm text-graphite/70 mb-6">Cliquer pour ouvrir son propre tableau de bord.</p>

                <!--
                    Bandeau de succes apres creation directe (retour du
                    ministere, 2026-09-12, point 03) : propose immediatement
                    d'inviter le titulaire (Pasteur) de la nouvelle entite -
                    desormais le seul "code"/lien restant a transmettre,
                    via le mecanisme d'invitation deja existant (point 11).
                -->
                <div v-if="createdOrgUnitId" class="glass-panel rounded-2xl p-5 border-forest/40 mb-6 animate-[fadeInUp_0.4s_ease-out_both]">
                    <p class="text-sm font-medium text-forest mb-3">« {{ createdOrgUnitName }} » a été créée et rattachée ici. Vous pouvez maintenant en inviter le/la titulaire :</p>
                    <a :href="`/org-units/${createdOrgUnitId}/inviter`"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-4 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20">
                        Inviter le/la titulaire de « {{ createdOrgUnitName }} »
                    </a>
                </div>

                <!--
                    Formulaire de creation directe (retour du ministere,
                    2026-09-12, point 03) : remplace le code de rattachement.
                    On reste sur ce tableau de bord ; le niveau ne propose que
                    des rangs strictement inferieurs a celui-ci, et
                    parent/ministere/chemin sont herites du noeud courant,
                    jamais saisis - meme regle qu'avant, sans code a generer.
                -->
                <form v-if="showCreateForm" @submit.prevent="submitCreate"
                    class="glass-panel rounded-3xl p-6 space-y-4 mb-6 animate-[fadeInUp_0.3s_ease-out_both]">
                    <h3 class="text-2xl font-bold text-graphite/62 uppercase tracking-widest">Nouvelle entité rattachée à {{ orgUnit.name }}</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Nom</label>
                            <input v-model="createForm.name" type="text" required placeholder="Ex. « Église APC Cotonou »"
                                class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/45 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="createForm.errors.name" class="mt-1 text-xs text-rose-600">{{ createForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Niveau</label>
                            <select v-model="createForm.level_rank" required class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option value="" disabled class="bg-white text-graphite">Choisir un niveau</option>
                                <option v-for="opt in availableChildRanks" :key="opt.rank" :value="opt.rank" class="bg-white text-graphite">{{ opt.label }}</option>
                            </select>
                            <p v-if="createForm.errors.level_rank" class="mt-1 text-xs text-rose-600">{{ createForm.errors.level_rank }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-graphite/58">Se rattache automatiquement à {{ orgUnit.name }} : aucun code à générer.</p>
                    <p v-if="orgUnitsRemainingLabel"
                        class="text-xs rounded-lg px-3 py-2"
                        :class="subscription.orgUnitsCount >= (subscription.orgUnitsLimit ?? Infinity) ? 'text-rose-700 bg-rose-50 border border-rose-200' : 'text-graphite/70 bg-graphite/5 border border-graphite/10'">
                        {{ orgUnitsRemainingLabel }}
                    </p>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="createForm.processing"
                            class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
                            Créer et rattacher ici
                        </button>
                        <button type="button" @click="showCreateForm = false; createForm.reset()"
                            class="inline-flex items-center gap-1.5 text-graphite/60 hover:text-graphite text-sm font-medium px-2 py-2">
                            Annuler
                        </button>
                    </div>
                </form>

                <div v-if="children.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <a v-for="child in children" :key="child.id" :href="`/org-units/${child.id}`"
                        class="group glass-panel rounded-3xl p-6 hover:border-graphite/20 hover:-translate-y-1 transition-all duration-300">
                        <span class="text-xs uppercase tracking-widest text-sanctuary/90 font-semibold block mb-1">{{ child.level_label }}</span>
                        <p class="font-semibold text-graphite text-[15px]">{{ child.name }}</p>
                    </a>
                </div>
                <p v-else-if="!showCreateForm" class="text-sm text-graphite/70">
                    Les prochaines entités rattachées à {{ orgUnit.name }} apparaîtront ici.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
