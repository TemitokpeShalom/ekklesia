<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import MinistryLetterhead from '@/Components/MinistryLetterhead.vue'
import LineChart from '@/Components/LineChart.vue'

/**
 * Document d'archive - rapport d'activités validé (chantier "Documents",
 * 2026-09-12). Lecture seule : une fois un rapport validé et verrouillé
 * (voir ActivityReportController::validateReport/abortIfValidated), ce
 * gabarit en est la version "papier" officielle, au même format que
 * l'affiche/le calendrier (barre de navigation sombre masquée à
 * l'impression, contenu clair au format A4 en dessous).
 *
 * Ordre demandé explicitement par le ministère : entête du ministère,
 * puis titre + période + église concernée + sa lignée hiérarchique
 * complète, puis le contenu du rapport.
 */
const props = defineProps({
    orgUnit: Object,
    ministry: Object,
    ancestry: Array,
    pastorName: String,
    period: String,
    monthLabel: String,
    report: Object,
    cultes: Array,
})

function print() {
    window.print()
}

// Corrige le 2026-09-12 (retour du ministere : "invalide date, invalide
// date") : voir Activites/Rapport.vue - meme cause (decoupage manuel d'une
// date Eloquent serialisee en ISO complet), meme correction.
function dateLabel(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function validatedLabel(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const attendanceLabels = computed(() => props.cultes.map((c) => dateLabel(c.service_date)))
const attendanceSeries = computed(() => [
    { name: 'Adultes', color: '#003080', values: props.cultes.map((c) => c.attendance_adults ?? 0) },
    { name: 'Enfants', color: '#b98a3e', values: props.cultes.map((c) => c.attendance_children ?? 0) },
])
</script>

<template>
    <div class="min-h-screen bg-night print:bg-white">
        <header class="border-b border-white/10 glass-panel px-6 py-5 flex items-center justify-between print:hidden">
            <div>
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold">{{ orgUnit.level_label }}</p>
                <h1 class="font-serif text-[2.5rem] font-bold text-white">{{ orgUnit.name }}</h1>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <Link :href="`/org-units/${orgUnit.id}/documents/rapports`" class="text-white/60 hover:text-white transition">Retour aux rapports</Link>
                <button @click="print"
                    class="rounded-full bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white px-5 py-2 text-sm font-semibold shadow-md shadow-azure/20">
                    Imprimer / Télécharger
                </button>
            </nav>
        </header>

        <main class="max-w-3xl mx-auto px-6 py-10 print:max-w-none print:px-0 print:py-0">
            <div class="bg-white border border-coffee/10 rounded-3xl print:rounded-none print:border-0 p-8 md:p-10 print:min-h-screen">
                <MinistryLetterhead :ministry="ministry" />

                <div class="text-center mb-8">
                    <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold">Rapport d'activités</p>
                    <h2 class="font-serif text-5xl font-bold text-ink mt-1 capitalize">{{ monthLabel }}</h2>

                    <!-- Eglise concernee et sa lignee hierarchique complete, juste apres l'entete, comme demande. -->
                    <p class="text-sm text-coffee-light mt-3">{{ orgUnit.name }} <span class="text-xs">({{ orgUnit.level_label }})</span></p>
                    <p v-if="ancestry.length" class="text-xs text-coffee-light/80 mt-1">
                        <span v-for="(a, i) in ancestry" :key="i">{{ a.name }}<span v-if="i < ancestry.length - 1"> › </span></span>
                    </p>
                    <p v-if="pastorName" class="text-xs text-coffee-light/80 mt-0.5">Pasteur : {{ pastorName }}</p>
                </div>

                <section class="border-t border-coffee/10 pt-6 mb-6">
                    <h3 class="mb-3 text-2xl font-bold text-azure uppercase tracking-widest">Baptêmes et nouveaux convertis</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <p><span class="text-coffee-light">Baptêmes : </span><span class="font-medium text-ink">{{ report.baptisms_count ?? '—' }}</span></p>
                        <p><span class="text-coffee-light">Nouveaux convertis : </span><span class="font-medium text-ink">{{ report.new_converts_count ?? '—' }}</span></p>
                    </div>
                </section>

                <section v-if="cultes.length > 0" class="border-t border-coffee/10 pt-6 mb-6">
                    <h3 class="mb-3 text-2xl font-bold text-azure uppercase tracking-widest">Évolution des effectifs</h3>
                    <LineChart :labels="attendanceLabels" :series="attendanceSeries" />
                </section>

                <section class="border-t border-coffee/10 pt-6 mb-6">
                    <h3 class="mb-3 text-2xl font-bold text-azure uppercase tracking-widest">Cultes tenus ce mois ({{ cultes.length }})</h3>
                    <div v-if="cultes.length === 0" class="text-sm text-coffee-light">Aucun culte enregistré ce mois-ci.</div>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-coffee/10 text-left text-xs uppercase tracking-widest text-coffee-light">
                                <th class="py-2 font-semibold">Date</th>
                                <th class="py-2 font-semibold">Culte</th>
                                <th class="py-2 font-semibold text-right">Adultes</th>
                                <th class="py-2 font-semibold text-right">Enfants</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="culte in cultes" :key="culte.id" class="border-b border-coffee/10 last:border-0">
                                <td class="py-2 text-coffee-light whitespace-nowrap">{{ dateLabel(culte.service_date) }}</td>
                                <td class="py-2 text-ink">{{ culte.title }}</td>
                                <td class="py-2 text-right text-ink">{{ culte.attendance_adults ?? '—' }}</td>
                                <td class="py-2 text-right text-ink">{{ culte.attendance_children ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section v-if="report.activities_notes" class="border-t border-coffee/10 pt-6 mb-6">
                    <h3 class="mb-2 text-2xl font-bold text-azure uppercase tracking-widest">Activités du mois</h3>
                    <p class="text-sm text-ink whitespace-pre-line">{{ report.activities_notes }}</p>
                </section>

                <section v-if="report.remarks" class="border-t border-coffee/10 pt-6 mb-6">
                    <h3 class="mb-2 text-2xl font-bold text-azure uppercase tracking-widest">Remarques et suggestions</h3>
                    <p class="text-sm text-ink whitespace-pre-line">{{ report.remarks }}</p>
                </section>

                <section v-if="report.leader_notes" class="border-t border-coffee/10 pt-6 mb-6">
                    <h3 class="mb-2 text-2xl font-bold text-azure uppercase tracking-widest">Situation du responsable</h3>
                    <p class="text-sm text-ink whitespace-pre-line">{{ report.leader_notes }}</p>
                    <p class="mt-1 text-[11px] text-coffee-light/70">Réservé à la hiérarchie pastorale directe, jamais visible dans une consolidation générale.</p>
                </section>

                <p class="border-t border-coffee/10 pt-4 mt-8 text-xs text-coffee-light/80 text-center">
                    Rapport validé et archivé le {{ validatedLabel(report.validated_at) }}<span v-if="report.validator"> par {{ report.validator.name }}</span>.
                </p>
                <p class="hidden print:block text-center text-[9px] text-coffee-light/50 mt-1">Document généré par Oikonema</p>
            </div>
        </main>
    </div>
</template>
