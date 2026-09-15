<script setup>
import { computed } from 'vue'

/**
 * Page d'erreur habillee (2026-09-14, retour du ministere : "un ecran
 * noir... ca fait peur, on croit qu'il y a un probleme dans la
 * plateforme"). Remplace la page Symfony brute (fond noir, texte blanc,
 * "403 | THIS ACTION IS UNAUTHORIZED") que Laravel affichait jusqu'ici pour
 * toute erreur HTTP (droits insuffisants, page introuvable, session
 * expiree, trop de tentatives, erreur serveur) - voir bootstrap/app.php,
 * withExceptions(). Page volontairement autonome (pas de AppLayout : le
 * contexte orgUnit n'est pas toujours disponible au moment d'une erreur),
 * mais avec la meme identite visuelle (fond clair, lisere vin -> or) que le
 * reste de la plateforme, pour ne jamais donner l'impression d'un site en
 * panne.
 *
 * Corrige le 2026-09-15 (retour du ministere : le fond noir avait bien
 * disparu, mais un code technique restait affiche - "un code erreur 400
 * quelque chose... je ne vais meme pas garder le chiffre en tete" - alors
 * qu'un message simple et poli suffit) : le code HTTP (status) sert
 * uniquement en interne a choisir le bon texte ci-dessous, il n'apparait
 * plus nulle part a l'ecran.
 */
const props = defineProps({
    status: { type: Number, required: true },
})

const MESSAGES = {
    403: {
        title: 'Action non autorisée',
        message:
            "Vous n'avez pas l'autorisation de faire cette action. Si vous pensez qu'il s'agit d'une erreur, contactez le responsable de votre ministère.",
    },
    404: {
        title: 'Page introuvable',
        message: "La page que vous cherchez n'existe pas, ou a peut-être été déplacée.",
    },
    419: {
        title: 'Session expirée',
        message:
            'Votre session a expiré, probablement après un long moment sans activité. Merci de vous reconnecter, puis de réessayer.',
    },
    429: {
        title: 'Trop de tentatives',
        message: 'Merci de patienter un instant avant de réessayer.',
    },
    500: {
        title: 'Un problème est survenu',
        message:
            "Une erreur technique est survenue de notre côté, pas la vôtre. Merci de réessayer dans un instant ; si cela se reproduit, signalez-le via l'assistant, en bas de l'écran.",
    },
    503: {
        title: 'Plateforme momentanément indisponible',
        message: 'La plateforme est en cours de maintenance ou momentanément surchargée. Merci de réessayer dans quelques instants.',
    },
}

const content = computed(() => MESSAGES[props.status] ?? MESSAGES[500])
</script>

<template>
    <div class="min-h-screen bg-paper text-graphite flex flex-col">
        <div class="h-1 bg-gradient-to-r from-sanctuary via-sanctuary-light to-gold" aria-hidden="true"></div>

        <div class="flex-1 flex items-center justify-center px-6 py-16">
            <div class="glass-panel max-w-lg w-full rounded-3xl px-8 py-10 text-center animate-[fadeInUp_0.5s_ease-out_both]">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-rose-50 text-rose-500 border border-rose-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-7 w-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007M4.93 4.93a10 10 0 1 0 14.14 14.14A10 10 0 0 0 4.93 4.93Z" />
                    </svg>
                </span>

                <h1 class="mt-5 font-serif text-2xl font-bold text-graphite">{{ content.title }}</h1>
                <p class="mt-3 text-sm text-graphite/65 leading-relaxed">{{ content.message }}</p>

                <a
                    href="/"
                    class="mt-8 inline-flex items-center justify-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-6 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20"
                >
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</template>
