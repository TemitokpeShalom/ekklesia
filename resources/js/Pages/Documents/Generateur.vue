<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
defineProps({
    orgUnit: Object,
})

const templates = [
    {
        key: 'trombinoscope', label: 'Trombinoscope', desc: 'Grille de photos des membres actifs',
        href: (id) => `/org-units/${id}/trombinoscope`,
        badge: 'from-gold to-gold-dark',
        icon: 'M3 9a2 2 0 012-2h.5l1-1.5h11l1 1.5H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z',
        icon2: 'M12 13m-3.2 0a3.2 3.2 0 106.4 0a3.2 3.2 0 10-6.4 0',
    },
    {
        key: 'affiche', label: 'Affiche', desc: "Identité de l'entité, responsables, effectif",
        href: (id) => `/org-units/${id}/documents/affiche`,
        badge: 'from-sanctuary to-sanctuary-dark',
        icon: 'M4 4h16v16H4V4zM4 9h16M9 4v16',
    },
    {
        key: 'calendrier', label: 'Calendrier annuel', desc: '12 pages, anniversaires du mois',
        href: (id) => `/org-units/${id}/documents/calendrier`,
        badge: 'from-forest to-forest/70',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
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
                <h1 class="font-serif text-3xl text-graphite mt-2">Générateur de documents</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">Trois gabarits imprimables, alimentés par les mêmes fiches membres.</p>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 animate-[fadeInUp_0.55s_ease-out_both]">
            <a v-for="t in templates" :key="t.key" :href="t.href(orgUnit.id)"
                class="group glass-panel rounded-3xl p-6 hover:border-graphite/20 hover:-translate-y-1 transition-all duration-300">
                <span :class="t.badge" class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-4 bg-gradient-to-br text-white shadow-md group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="t.icon" />
                        <path v-if="t.icon2" stroke-linecap="round" stroke-linejoin="round" :d="t.icon2" />
                    </svg>
                </span>
                <p class="font-semibold text-graphite text-[15px]">{{ t.label }}</p>
                <p class="text-xs text-graphite/65 mt-1">{{ t.desc }}</p>
            </a>
        </div>
    </AppLayout>
</template>
