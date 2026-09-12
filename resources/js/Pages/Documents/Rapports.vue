<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Archive des rapports valides, mois par mois (2026-09-12) - demande
 * explicite du ministere : "je voulais qu'on trouve tous les differents
 * rapports ranges mois par mois... et des que vous cliquez sur un mois,
 * vous avez le rapport directement en PDF format A4". Un mois n'apparait
 * ouvrable ici QUE si le rapport correspondant a ete valide (voir
 * RapportsArchiveController) - un rapport non valide n'est pas encore un
 * document d'archive, seulement un brouillon modifiable.
 */
/**
 * Corrige le 2026-09-12 (retour du ministere, chantier "module Finances") :
 * "des qu'il valide... le message... remonte au niveau haut" -
 * childReports liste les rapports (activités/finances) recemment validés
 * par les entités DIRECTEMENT en dessous, avec un lien immédiat vers leur
 * PDF (voir RapportsArchiveController::recentChildReports). Vide pour une
 * Eglise locale/Cellule (rien en dessous) - n'apparaît que pour un niveau
 * qui a des entités filles.
 */
defineProps({
    orgUnit: Object,
    months: Array,
    inventoryYears: Array,
    childReports: Array,
})

function dateLabel(value) {
    if (!value) return ''
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function periodLabel(value) {
    const [year, month] = value.split('-')
    return new Date(year, month - 1, 1).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/documents`" back-label="Retour aux documents">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Documents · Rapports
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">Rapports validés</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">Un mois n'apparaît disponible ici qu'une fois son rapport validé — validez-le d'abord depuis le module Finances ou Activités pour l'archiver.</p>
            </div>
        </template>

        <div v-if="childReports?.length" class="max-w-3xl mx-auto mb-8 animate-[fadeInUp_0.5s_ease-out_both]">
            <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-3">Rapports transmis par les entités en dessous</h2>
            <div class="space-y-2">
                <a v-for="(r, i) in childReports" :key="i"
                    :href="`/org-units/${r.org_unit.id}/documents/rapports/${r.type}/${r.period}`"
                    class="flex items-center justify-between gap-4 glass-panel rounded-2xl px-5 py-3.5 hover:border-azure/30 transition-colors">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-graphite truncate">
                            {{ r.org_unit.name }} <span class="text-xs font-normal text-graphite/55">({{ r.org_unit.level_label }})</span>
                        </p>
                        <p class="text-xs text-graphite/62 capitalize">
                            {{ r.type === 'finance' ? 'Rapport financier' : "Rapport d'activités" }} · {{ periodLabel(r.period) }}
                            <span v-if="r.validator_name"> · validé par {{ r.validator_name }}</span>
                        </p>
                    </div>
                    <span class="flex-shrink-0 text-xs font-semibold text-azure bg-azure/10 rounded-full px-3 py-1.5">Consulter</span>
                </a>
            </div>
        </div>

        <div class="max-w-3xl mx-auto space-y-3 animate-[fadeInUp_0.55s_ease-out_both]">
            <div v-for="m in months" :key="m.period" class="glass-panel rounded-2xl px-5 py-4 flex items-center justify-between gap-4">
                <p class="font-serif text-lg text-graphite capitalize shrink-0">{{ m.label }}</p>

                <div class="flex items-center gap-2 flex-wrap justify-end">
                    <a v-if="m.activityValidatedAt"
                        :href="`/org-units/${orgUnit.id}/documents/rapports/activite/${m.period}`"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold bg-slateblue/10 text-slateblue hover:bg-slateblue/15 rounded-full px-3.5 py-1.5 transition-colors">
                        Rapport d'activités
                    </a>
                    <span v-else class="text-xs text-graphite/40 bg-graphite/5 rounded-full px-3.5 py-1.5">Activités non validées</span>

                    <a v-if="m.financeValidatedAt"
                        :href="`/org-units/${orgUnit.id}/documents/rapports/finance/${m.period}`"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold bg-forest/10 text-forest hover:bg-forest/15 rounded-full px-3.5 py-1.5 transition-colors">
                        Rapport financier
                    </a>
                    <span v-else class="text-xs text-graphite/40 bg-graphite/5 rounded-full px-3.5 py-1.5">Finances non validées</span>
                </div>
            </div>
        </div>

        <!--
            Chantier "module Inventaire" (2026-09-12, retour du ministere) :
            "c'est un document qui manque" - une fiche d'inventaire par
            annee, meme emplacement que les rapports mensuels mais dans son
            propre bloc puisque sa cadence est annuelle.
        -->
        <div v-if="inventoryYears?.length" class="max-w-3xl mx-auto mt-10 animate-[fadeInUp_0.58s_ease-out_both]">
            <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-3">Fiches d'inventaire annuelles</h2>
            <div class="space-y-3">
                <div v-for="y in inventoryYears" :key="y.year" class="glass-panel rounded-2xl px-5 py-4 flex items-center justify-between gap-4">
                    <p class="font-serif text-lg text-graphite shrink-0">{{ y.year }}</p>
                    <a v-if="y.validatedAt"
                        :href="`/org-units/${orgUnit.id}/documents/rapports/inventaire/${y.year}`"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold bg-azure/10 text-azure hover:bg-azure/15 rounded-full px-3.5 py-1.5 transition-colors">
                        Fiche d'inventaire
                    </a>
                    <span v-else class="text-xs text-graphite/40 bg-graphite/5 rounded-full px-3.5 py-1.5">Inventaire non validé</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
