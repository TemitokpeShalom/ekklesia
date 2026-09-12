<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import MinistryLetterhead from '@/Components/MinistryLetterhead.vue'
import LineChart from '@/Components/LineChart.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 *
 * Corrige le 2026-09-12 (retour du ministere, "refaire un petit match en
 * arriere vers la presentation du rapport d'activite... comme precise
 * pour le rapport financier") :
 * - bloc "position" ajoute (chaine hierarchique + nom du pasteur) ;
 * - "Retour" pointait vers Finances (heritage de l'ancien lien dans ce
 *   module) - ce rapport a maintenant sa propre tuile sur le tableau de
 *   bord, independante des Finances : "Retour" ramene donc au tableau de
 *   bord, comme partout ailleurs ;
 * - baptisms_count n'est plus saisi a la main - calcule automatiquement
 *   depuis le module Sacrements (voir ActivityReportController) ;
 * - couleurs (boutons, titres de section) alignees sur le bleu azur,
 *   comme tous les autres modules deja corriges ;
 * - graphe d'evolution des effectifs (courbe) ajoute.
 */
const props = defineProps({
    orgUnit: Object,
    month: String,
    ministry: Object,
    ancestry: Array,
    pastorName: String,
    report: Object,
    cultes: Array,
    baptismsAuto: Number,
    canManage: Boolean,
})

const form = useForm({
    month: props.month,
    new_converts_count: props.report?.new_converts_count ?? '',
    activities_notes: props.report?.activities_notes ?? '',
    remarks: props.report?.remarks ?? '',
    leader_notes: props.report?.leader_notes ?? '',
})

// Attendus par le graphe d'evolution : une serie Adultes, une serie
// Enfants, une valeur par culte du mois, dans l'ordre chronologique deja
// fourni par le controleur.
const attendanceLabels = computed(() => props.cultes.map((c) => dateLabel(c.service_date)))
const attendanceSeries = computed(() => [
    { name: 'Adultes', color: '#003080', values: props.cultes.map((c) => c.attendance_adults ?? 0) },
    { name: 'Enfants', color: '#b98a3e', values: props.cultes.map((c) => c.attendance_children ?? 0) },
])

// Validation (chantier "Documents", 2026-09-12) : un rapport valide
// alimente l'archive mensuelle (voir RapportsArchiveController) et se
// verrouille - on ne veut pas qu'une archive deja remise/imprimee change
// silencieusement sous les pieds de qui l'a recue.
const isValidated = computed(() => !!props.report?.validated_at)

function validatedLabel(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function validate() {
    router.post(`/org-units/${props.orgUnit.id}/rapport-activites/valider`, { month: props.month })
}

function unlock() {
    if (!confirm('Déverrouiller ce rapport pour le corriger ? Il ne sera plus visible dans les archives tant qu\'il ne sera pas revalidé.')) return
    router.post(`/org-units/${props.orgUnit.id}/rapport-activites/deverrouiller`, { month: props.month })
}

function monthLabel(value) {
    const [year, month] = value.split('-')
    return new Date(year, month - 1, 1).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
}

// Corrige le 2026-09-12 (retour du ministere : "actuellement la date met
// invalide date") : `culte.service_date` est une date Eloquent serialisee
// en JSON au format ISO complet ("2026-09-08T00:00:00.000000Z"), jamais
// juste "2026-09-08" - le decoupage manuel par tiret ci-dessus cassait
// des que le troisieme morceau ("08T00:00:00.000000Z") n'etait plus un
// nombre pur. `new Date(value)` comprend nativement les deux formats
// (deja la methode utilisee sans probleme ailleurs, ex. Bibliotheque).
function dateLabel(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function changeMonth(event) {
    router.get(`/org-units/${props.orgUnit.id}/rapport-activites`, { mois: event.target.value })
}

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/rapport-activites`)
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Rapport d'activités
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2 capitalize">{{ monthLabel(month) }}</h1>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-8">
            <MinistryLetterhead :ministry="ministry" />

            <!--
                Bloc "position" (retour du ministere, 2026-09-12 : meme
                demande que pour le rapport financier).
            -->
            <div v-if="ancestry?.length || pastorName" class="text-center -mt-2 animate-[fadeInUp_0.52s_ease-out_both]">
                <p v-if="ancestry?.length" class="text-xs text-graphite/62">
                    <span v-for="(a, i) in ancestry" :key="i">{{ a.name }}<span class="text-graphite/40"> ({{ a.label }})</span><span v-if="i < ancestry.length - 1"> › </span></span>
                </p>
                <p v-if="pastorName" class="text-xs text-graphite/62 mt-0.5">Pasteur : {{ pastorName }}</p>
            </div>

            <div class="flex items-center justify-between gap-4 animate-[fadeInUp_0.5s_ease-out_both] print:hidden">
                <div v-if="isValidated" class="text-sm text-forest bg-forest/10 border border-forest/30 rounded-xl px-4 py-2.5">
                    Validé et archivé le {{ validatedLabel(report.validated_at) }}<span v-if="report.validator_name"> par {{ report.validator_name }}</span>.
                    <a :href="`/org-units/${orgUnit.id}/documents/rapports/activite/${month}`" class="underline font-medium">Voir dans les archives</a>
                </div>
                <div v-else class="text-sm text-graphite/60">Rapport non validé — pas encore archivé dans Documents › Rapports.</div>

                <input
                    type="month"
                    :value="month"
                    @change="changeMonth"
                    class="bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"
                />
            </div>

            <div v-if="canManage" class="flex justify-end gap-2 animate-[fadeInUp_0.52s_ease-out_both] print:hidden">
                <button v-if="!isValidated && report" type="button" @click="validate"
                    class="inline-flex items-center gap-1.5 bg-forest hover:bg-forest/90 transition-colors text-white rounded-xl px-4 py-2 text-sm font-semibold">
                    Valider et archiver
                </button>
                <button v-else type="button" @click="unlock"
                    class="inline-flex items-center gap-1.5 bg-graphite/5 hover:bg-graphite/10 transition-colors text-graphite/80 rounded-xl px-4 py-2 text-sm font-semibold">
                    Déverrouiller pour corriger
                </button>
            </div>

            <!--
                Corrige le 2026-09-11 (retour du ministere) : une v1 de cet
                encart affichait un total "Adultes"/"Enfants" obtenu en
                additionnant les effectifs de chaque culte du mois - retire
                ensuite entierement, a la demande explicite du ministere :
                une meme personne presente a plusieurs cultes dans le mois y
                est comptee autant de fois, ce total n'a donc aucun sens et
                fausse la lecture ("la meme personne... sera comptee trois
                fois"). Contrairement aux finances (ou un bilan global a du
                sens, une devise ne se "represente" pas deux fois), les
                effectifs ne se totalisent JAMAIS sur plusieurs cultes -
                seul le nombre de cultes tenus est un total legitime.
            -->
            <section class="glass-panel rounded-3xl p-6 animate-[fadeInUp_0.55s_ease-out_both]">
                <h3 class="mb-4 text-xs font-bold text-azure uppercase tracking-widest">Cultes tenus ce mois</h3>
                <p class="text-xl font-serif text-graphite">{{ cultes.length }}</p>
                <p class="mt-4 text-xs text-graphite/58">Le détail (date, présences) de chaque culte est ci-dessous. Aucun total d'effectifs n'est calculé : une même personne présente à plusieurs cultes du mois y serait comptée plusieurs fois.</p>
            </section>

            <!--
                Graphe d'evolution des effectifs (retour du ministere,
                2026-09-12 : "une presentation graphique de l'evolution des
                effectifs sous forme d'une courbe... pour montrer comment
                les effectifs evoluent chaque dimanche").
            -->
            <section v-if="cultes.length > 0" class="glass-panel rounded-3xl p-6 animate-[fadeInUp_0.56s_ease-out_both]">
                <h3 class="mb-4 text-xs font-bold text-azure uppercase tracking-widest">Évolution des effectifs</h3>
                <LineChart :labels="attendanceLabels" :series="attendanceSeries" />
            </section>

            <section class="glass-panel rounded-3xl p-6 animate-[fadeInUp_0.57s_ease-out_both]">
                <h3 class="mb-4 text-xs font-bold text-azure uppercase tracking-widest">Détail par culte</h3>
                <div v-if="cultes.length === 0" class="text-sm text-graphite/62">Aucun culte enregistré ce mois-ci.</div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-graphite/10 text-left text-xs uppercase tracking-widest text-graphite/55">
                            <th class="py-2 font-semibold">Date</th>
                            <th class="py-2 font-semibold">Culte</th>
                            <th class="py-2 font-semibold text-right">Adultes</th>
                            <th class="py-2 font-semibold text-right">Enfants</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="culte in cultes" :key="culte.id" class="border-b border-graphite/10 last:border-0">
                            <td class="py-2 text-graphite/70 whitespace-nowrap">{{ dateLabel(culte.service_date) }}</td>
                            <td class="py-2 text-graphite/87">{{ culte.title }}</td>
                            <td class="py-2 text-right text-graphite">{{ culte.attendance_adults ?? '—' }}</td>
                            <td class="py-2 text-right text-graphite">{{ culte.attendance_children ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.6s_ease-out_both]">
                <p v-if="isValidated" class="text-xs text-graphite/55 -mt-2">Ce rapport est validé et verrouillé : déverrouillez-le ci-dessus pour modifier les champs ci-dessous.</p>
                <fieldset :disabled="!canManage || isValidated" class="space-y-8">
                    <section>
                        <h2 class="text-xs font-bold text-azure uppercase tracking-widest mb-4">Baptêmes et nouveaux convertis</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-graphite/87">Baptêmes</label>
                                <div class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm flex items-center justify-between">
                                    <span class="font-semibold">{{ baptismsAuto }}</span>
                                    <span class="text-xs text-graphite/50">calculé automatiquement</span>
                                </div>
                                <p class="mt-1 text-xs text-graphite/55">Nombre de baptêmes enregistrés ce mois dans le module Sacrements — jamais ressaisi ici, juste le chiffre, pas les noms.</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-graphite/87">Nouveaux convertis</label>
                                <input v-model="form.new_converts_count" type="number" min="0" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                                <p class="mt-1 text-xs text-graphite/55">Un chiffre global (ex. « 10 »), jamais les noms des personnes.</p>
                            </div>
                        </div>
                    </section>

                    <section class="border-t border-graphite/10 pt-6">
                        <h2 class="text-xs font-bold text-azure uppercase tracking-widest mb-4">Activités du mois</h2>
                        <textarea v-model="form.activities_notes" rows="3" placeholder="Évangélisations, réveils, événements particuliers..." class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                    </section>

                    <section class="border-t border-graphite/10 pt-6">
                        <h2 class="text-xs font-bold text-azure uppercase tracking-widest mb-4">Remarques et suggestions</h2>
                        <textarea v-model="form.remarks" rows="3" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                    </section>

                    <section class="border-t border-graphite/10 pt-6">
                        <h2 class="text-xs font-bold text-azure uppercase tracking-widest mb-4">Situation du responsable</h2>
                        <textarea v-model="form.leader_notes" rows="3" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                        <p class="mt-1 text-xs text-graphite/58">Réservé à la hiérarchie pastorale directe, jamais visible dans une consolidation générale.</p>
                    </section>
                </fieldset>

                <div v-if="canManage && !isValidated" class="border-t border-graphite/10 pt-6">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20 disabled:opacity-60"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Enregistrer
                    </button>
                </div>
            </form>

            <!-- Note discrete (2026-09-11), uniquement visible a l'impression : le logo Oikonema est masque sur le rapport (voir AppLayout.vue), seule cette mention texte, tres petite, rappelle qui a genere le document. -->
            <p class="hidden print:block text-center text-[9px] text-graphite/40">Document généré par Oikonema</p>
        </div>
    </AppLayout>
</template>
