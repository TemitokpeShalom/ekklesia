<script setup>
import { Link } from '@inertiajs/vue3'

/**
 * Coquille partagee v3 "Vitrail" (2026-09-08).
 *
 * Jusqu'ici chaque page Inertia reconstruisait son propre en-tete (voir
 * Dashboard/Index.vue, Members/Index.vue...) : trois generations de style
 * coexistaient (slate generique, "v2" parchemin/sanctuary, et maintenant
 * v3). Cette coquille centralise l'identite v3 -- fond sombre chaleureux,
 * verre depoli, halo dore, motif de rosace en filigrane -- pour que
 * chaque migration d'ecran suivante consiste a envelopper son contenu
 * dans <AppLayout> plutot qu'a recopier un en-tete. Rien n'est retire des
 * anciennes pages : elles continuent d'utiliser leur propre balisage tant
 * qu'elles n'ont pas ete migrees.
 */
defineProps({
    orgUnit: { type: Object, default: null },
    // Lien "retour" optionnel (ex: retour au tableau de bord depuis un module).
    backHref: { type: String, default: null },
    backLabel: { type: String, default: 'Retour au tableau de bord' },
})
</script>

<template>
    <div class="min-h-screen bg-night text-white/90 relative overflow-x-hidden">
        <!-- Fond : halos qui derivent tres lentement + rosace en filigrane -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-40 -right-24 w-[34rem] h-[34rem] bg-sanctuary/25 rounded-full blur-[110px]" style="animation: driftGlow 19s ease-in-out infinite;"></div>
            <div class="absolute top-1/3 -left-32 w-[30rem] h-[30rem] bg-gold/10 rounded-full blur-[110px]" style="animation: driftGlow 23s ease-in-out infinite reverse;"></div>
            <div class="absolute bottom-[-10rem] right-1/4 w-[26rem] h-[26rem] bg-forest/12 rounded-full blur-[110px]" style="animation: driftGlow 27s ease-in-out infinite;"></div>
            <div class="absolute inset-0 bg-vitrail"></div>

            <svg class="absolute left-1/2 top-24 -translate-x-1/2 w-[46rem] h-[46rem] opacity-[0.05] hidden md:block"
                viewBox="0 0 200 200" fill="none" stroke="#f1e4c8" stroke-width="0.6">
                <circle cx="100" cy="100" r="70" />
                <circle cx="100" cy="100" r="52" />
                <g v-for="n in 12" :key="n" :transform="`rotate(${n * 30} 100 100)`">
                    <line x1="100" y1="30" x2="100" y2="100" />
                </g>
                <circle cx="100" cy="100" r="14" />
            </svg>
        </div>

        <header class="relative z-10 sticky top-0 border-b border-white/[0.06] glass-panel">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
                <Link :href="orgUnit ? `/org-units/${orgUnit.id}` : '/'" class="flex items-center gap-3 min-w-0 group">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-gradient-to-br from-gold to-gold-dark flex items-center justify-center text-night font-serif font-bold text-[15px] shadow-glow-gold group-hover:scale-105 transition-transform duration-300">E</span>
                    <span class="min-w-0" v-if="orgUnit">
                        <span class="block text-[10.5px] uppercase tracking-widest text-gold-soft/80 font-semibold truncate">{{ orgUnit.level_label }}</span>
                        <span class="block font-serif text-white text-[15px] leading-tight truncate">{{ orgUnit.name }}</span>
                    </span>
                    <span class="font-serif text-white text-lg" v-else>Ekklesia</span>
                </Link>
                <nav class="flex items-center gap-2 shrink-0">
                    <slot name="actions" />
                    <Link v-if="backHref" :href="backHref" class="text-sm text-white/70 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 rounded-full px-4 py-2 transition-colors">{{ backLabel }}</Link>
                    <Link href="/aide" class="hidden sm:inline-flex text-sm text-white/70 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 rounded-full px-4 py-2 transition-colors">Aide</Link>
                    <form method="post" action="/deconnexion">
                        <button class="text-sm text-white/70 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 rounded-full px-4 py-2 transition-colors">Se déconnecter</button>
                    </form>
                </nav>
            </div>
            <div v-if="$slots.title" class="max-w-6xl mx-auto px-6 pb-6 -mt-1">
                <slot name="title" />
            </div>
        </header>

        <main class="relative z-10 max-w-6xl mx-auto px-6 py-10 md:py-14">
            <slot />
        </main>

        <footer class="relative z-10 max-w-6xl mx-auto px-6 pb-10 pt-4">
            <p class="text-xs text-white/30 text-center">Ekklesia — plateforme de gestion de ministère</p>
        </footer>
    </div>
</template>
