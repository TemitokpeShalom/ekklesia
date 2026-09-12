<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Revu le 2026-09-12 (retour du ministere : "pour moi le role de ce grand
 * module document reste incompris"). Le Trombinoscope est parti vivre sa
 * vie comme module a part entiere du tableau de bord (voir
 * Dashboard/Index.vue) - ce hub ne regroupe plus que ce qui est
 * effectivement UN DOCUMENT du ministere : l'affiche, le calendrier annuel,
 * l'archive des rapports valides, et les archives libres de l'entite.
 */
defineProps({
    orgUnit: Object,
})

const templates = [
    {
        key: 'rapports', label: 'Rapports', desc: 'Rapports validés, classés mois par mois, prêts à imprimer',
        href: (id) => `/org-units/${id}/documents/rapports`,
        badge: 'from-slateblue to-slateblue/70',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        key: 'affiche', label: 'Affiche', desc: "Identité de l'entité, responsables, effectif",
        href: (id) => `/org-units/${id}/documents/affiche`,
        badge: 'from-sanctuary to-sanctuary-dark',
        icon: 'M4 4h16v16H4V4zM4 9h16M9 4v16',
    },
    {
        key: 'calendrier', label: 'Calendrier annuel', desc: "Calendrier du ministère, jour par jour, avec les anniversaires",
        href: (id) => `/org-units/${id}/documents/calendrier`,
        badge: 'from-forest to-forest/70',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        key: 'archives', label: 'Archives', desc: "Documents propres à l'entité (statuts, actes, courriers...)",
        href: (id) => `/org-units/${id}/documents/archives`,
        badge: 'from-coffee to-coffee-dark',
        icon: 'M3 7.5l9-4.5 9 4.5m-18 0l9 4.5m-9-4.5v9l9 4.5m0-9l9-4.5m-9 4.5v9m9-13.5v9l-9 4.5',
    },
]
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Documents
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">Documents du ministère</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">Les rapports validés, l'affiche et le calendrier du ministère, et les archives propres à cette entité.</p>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 animate-[fadeInUp_0.55s_ease-out_both]">
            <a v-for="t in templates" :key="t.key" :href="t.href(orgUnit.id)"
                class="group glass-panel rounded-3xl p-6 hover:border-graphite/20 hover:-translate-y-1 transition-all duration-300">
                <span :class="t.badge" class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="t.icon" />
                    </svg>
                </span>
                <p class="font-semibold text-graphite text-[15px]">{{ t.label }}</p>
                <p class="text-xs text-graphite/65 mt-1">{{ t.desc }}</p>
            </a>
        </div>
    </AppLayout>
</template>
