<script setup>
import { router } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import MinistryLetterhead from '@/Components/MinistryLetterhead.vue'
import DonutChart from '@/Components/DonutChart.vue'

// Palette cyclique pour les graphes de repartition (retour du ministere,
// 2026-09-12 : "des graphes circulaires pour montrer... telles portions") -
// mêmes couleurs que le reste de la plateforme (azur, vert foret, vin,
// or, bleu ardoise, brun), jamais des couleurs inventees.
const PALETTE = ['#003080', '#2f6b4f', '#6e1f35', '#b98a3e', '#2e4c6d', '#7a6152']

function breakdownSegments(lines) {
    return lines.map((line, i) => ({
        label: line.account_label,
        value: line.total,
        color: PALETTE[i % PALETTE.length],
    }))
}

/**
 * Point 08 (rapports consolidés multidevises, 10/09/2026) : ce rapport
 * consolide désormais ce nœud et tous ses descendants (comme les autres
 * rapports consolidés de la plateforme), et n'additionne jamais deux
 * devises ensemble puisqu'aucune conversion de change n'existe dans
 * l'application : chaque devise rencontrée reçoit son propre bloc, avec
 * son propre détail et son propre solde.
 *
 * Corrige le 2026-09-12 (retour du ministere, chantier "module Finances") :
 * - bloc "position" ajouté (chaîne hiérarchique + nom du pasteur), déjà
 *   présent sur l'archive imprimable mais manquant ici sur le rapport
 *   "vivant" ;
 * - le détail par compte (encaissements/décaissements ci-dessous, groupé
 *   sur tout le mois) reste utile comme vue comptable, mais ne répond pas
 *   à "chaque mouvement doit apparaître l'un par l'un" - un journal
 *   chronologique complète donc chaque bloc devise, mouvement par
 *   mouvement, avec un solde d'ouverture et de clôture (voir
 *   FinanceReportBuilder) plutôt qu'un solde ligne à ligne, jugé plus
 *   lisible sur un document mensuel qu'un compte bancaire.
 */
const props = defineProps({
    orgUnit: Object,
    month: String,
    ministry: Object,
    ancestry: Array,
    pastorName: String,
    devises: Array,
    validation: Object,
    canManage: Boolean,
})

// Validation (chantier "Documents", 2026-09-12) : un rapport valide
// alimente l'archive mensuelle (voir RapportsArchiveController) et se fige
// - les chiffres affiches dans l'archive ne doivent plus bouger si des
// ecritures du mois sont corrigees ensuite.
const isValidated = computed(() => !!props.validation)

function validatedLabel(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function validate() {
    router.post(`/org-units/${props.orgUnit.id}/finances-rapport/valider`, { mois: props.month })
}

function unlock() {
    if (!confirm('Déverrouiller ce rapport ? Il ne sera plus visible dans les archives tant qu\'il ne sera pas revalidé.')) return
    router.post(`/org-units/${props.orgUnit.id}/finances-rapport/deverrouiller`, { mois: props.month })
}

function formatAmount(value, currency) {
    return new Intl.NumberFormat('fr-FR').format(value) + ' ' + currency
}

function formatLedgerDate(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function monthLabel(value) {
    const [year, month] = value.split('-')
    return new Date(year, month - 1, 1).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
}

function changeMonth(event) {
    router.get(`/org-units/${props.orgUnit.id}/finances-rapport`, { mois: event.target.value })
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/finances`" back-label="Retour aux finances">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Finances
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2 capitalize">Rapport financier · {{ monthLabel(month) }}</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">
                    Vue consolidée de ce niveau et de tous ses niveaux descendants. Aucune conversion de change n'existant dans l'application, chaque devise rencontrée est présentée dans son propre bloc, jamais mélangée à une autre.
                </p>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-8">
            <MinistryLetterhead :ministry="ministry" />

            <!--
                Bloc "position" (retour du ministere, 2026-09-12 : "juste
                apres l'en-tete... la position de l'eglise concernee : le
                pays, la region, le district... et le nom du pasteur").
            -->
            <div v-if="ancestry?.length || pastorName" class="text-center -mt-2 animate-[fadeInUp_0.52s_ease-out_both]">
                <p v-if="ancestry?.length" class="text-xs text-graphite/62">
                    <span v-for="(a, i) in ancestry" :key="i">{{ a.name }}<span class="text-graphite/40"> ({{ a.label }})</span><span v-if="i < ancestry.length - 1"> › </span></span>
                </p>
                <p v-if="pastorName" class="text-xs text-graphite/62 mt-0.5">Pasteur : {{ pastorName }}</p>
            </div>

            <div class="flex items-center justify-between gap-4 animate-[fadeInUp_0.5s_ease-out_both] print:hidden">
                <div v-if="isValidated" class="text-sm text-forest bg-forest/10 border border-forest/30 rounded-xl px-4 py-2.5">
                    Validé et archivé le {{ validatedLabel(validation.validated_at) }}<span v-if="validation.validator_name"> par {{ validation.validator_name }}</span>.
                    <a :href="`/org-units/${orgUnit.id}/documents/rapports/finance/${month}`" class="underline font-medium">Voir dans les archives</a>
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
                <button v-if="!isValidated" type="button" @click="validate"
                    class="inline-flex items-center gap-1.5 bg-forest hover:bg-forest/90 transition-colors text-white rounded-xl px-4 py-2 text-sm font-semibold">
                    Valider et archiver
                </button>
                <button v-else type="button" @click="unlock"
                    class="inline-flex items-center gap-1.5 bg-graphite/5 hover:bg-graphite/10 transition-colors text-graphite/80 rounded-xl px-4 py-2 text-sm font-semibold">
                    Déverrouiller
                </button>
            </div>

            <div v-if="devises.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center animate-[fadeInUp_0.55s_ease-out_both]">
                <p class="text-sm font-medium text-graphite/80">Aucun mouvement enregistré ce mois-ci, sur ce niveau ou ses descendants.</p>
            </div>

            <div v-for="devise in devises" :key="devise.currency" class="space-y-6 animate-[fadeInUp_0.55s_ease-out_both]">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    <h2 class="text-[1.75rem] font-bold text-sanctuary/90 uppercase tracking-widest">{{ devise.currency }}</h2>
                    <span v-if="devise.accountingStandardLabel" class="text-xs text-graphite/62">· Détail par compte comptable ({{ devise.accountingStandardLabel }})</span>
                    <span v-else class="text-xs text-graphite/62">· Détail par nature de mouvement</span>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
                    <div class="glass-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-graphite/68">Solde d'ouverture</p>
                        <p class="mt-2 text-lg font-serif text-graphite">{{ formatAmount(devise.openingBalance, devise.currency) }}</p>
                    </div>
                    <div class="glass-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-forest/90">Total encaissements</p>
                        <p class="mt-2 text-lg font-serif text-graphite">{{ formatAmount(devise.totalEncaissements, devise.currency) }}</p>
                    </div>
                    <div class="glass-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-sanctuary-light">Total décaissements</p>
                        <p class="mt-2 text-lg font-serif text-graphite">{{ formatAmount(devise.totalDecaissements, devise.currency) }}</p>
                    </div>
                    <div class="glass-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-graphite/68">Solde du mois</p>
                        <p class="mt-2 text-lg font-serif text-graphite">{{ formatAmount(devise.solde, devise.currency) }}</p>
                    </div>
                    <div class="glass-panel rounded-2xl p-5 bg-azure/5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-azure">Solde de clôture</p>
                        <p class="mt-2 text-lg font-serif text-graphite">{{ formatAmount(devise.closingBalance, devise.currency) }}</p>
                    </div>
                </div>

                <!--
                    Graphes circulaires (retour du ministere, 2026-09-12 :
                    "des graphes circulaires pour montrer que les entrées
                    ont occupé telles portions et les sorties ont occupé
                    telles portions... on peut même revenir encore pour
                    détailler aussi pour les entrées").
                -->
                <section class="glass-panel rounded-3xl p-6">
                    <h3 class="mb-4 text-2xl font-bold text-azure uppercase tracking-widest">Répartition</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <p class="mb-2 text-xs text-graphite/60">Encaissements / décaissements</p>
                            <DonutChart :segments="[
                                { label: 'Encaissements', value: devise.totalEncaissements, color: '#2f6b4f' },
                                { label: 'Décaissements', value: devise.totalDecaissements, color: '#8f3049' },
                            ]" :size="140" />
                        </div>
                        <div>
                            <p class="mb-2 text-xs text-graphite/60">Détail des encaissements</p>
                            <DonutChart :segments="breakdownSegments(devise.encaissements)" :size="140" />
                        </div>
                        <div>
                            <p class="mb-2 text-xs text-graphite/60">Détail des décaissements</p>
                            <DonutChart :segments="breakdownSegments(devise.decaissements)" :size="140" />
                        </div>
                    </div>
                </section>

                <!--
                    Journal chronologique (retour du ministere, 2026-09-12 :
                    "chaque ligne, chaque jour, chaque mouvement doit
                    apparaître l'un par l'un") - mouvement par mouvement,
                    jamais regroupé par compte comme les deux tableaux
                    "Encaissements"/"Décaissements" ci-dessous, qui restent
                    une vue comptable complémentaire (total par compte sur
                    tout le mois), pas un remplacement.
                -->
                <section class="glass-panel rounded-3xl p-6">
                    <h3 class="mb-4 text-2xl font-bold text-azure uppercase tracking-widest">Journal du mois, jour par jour</h3>
                    <div v-if="devise.ledger.length === 0" class="text-sm text-graphite/62">Aucun mouvement ce mois-ci.</div>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-widest text-graphite/50 border-b border-graphite/10">
                                <th class="py-2 font-semibold">Date</th>
                                <th class="py-2 font-semibold">Nature</th>
                                <th class="py-2 font-semibold">Détail</th>
                                <th class="py-2 font-semibold text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="line in devise.ledger" :key="line.id" class="border-b border-graphite/10 last:border-0">
                                <td class="py-2 text-graphite/65 whitespace-nowrap">{{ formatLedgerDate(line.date) }}</td>
                                <td class="py-2 text-graphite/87">
                                    {{ line.type_label }}
                                    <span v-if="line.account_code" class="text-graphite/50">({{ line.account_code }})</span>
                                </td>
                                <td class="py-2 text-graphite/62">
                                    {{ line.org_unit_name }}<span v-if="line.counterparty"> · {{ line.counterparty }}</span>
                                </td>
                                <td :class="['py-2 text-right font-medium', line.nature === 'encaissement' ? 'text-forest' : 'text-sanctuary-light']">
                                    {{ line.nature === 'encaissement' ? '+' : '-' }}{{ formatAmount(line.amount, devise.currency) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="glass-panel rounded-3xl p-6">
                    <h3 class="mb-4 text-2xl font-bold text-sanctuary/80 uppercase tracking-widest">Encaissements</h3>
                    <div v-if="devise.encaissements.length === 0" class="text-sm text-graphite/62">Aucun encaissement ce mois-ci.</div>
                    <table v-else class="w-full text-sm">
                        <tbody>
                            <tr v-for="line in devise.encaissements" :key="line.account_code || line.account_label" class="border-b border-graphite/10 last:border-0">
                                <td v-if="devise.accountingStandardLabel" class="py-2 text-graphite/65">{{ line.account_code }}</td>
                                <td class="py-2 text-graphite/87">{{ line.account_label }}</td>
                                <td class="py-2 text-right font-medium text-graphite">{{ formatAmount(line.total, devise.currency) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="glass-panel rounded-3xl p-6">
                    <h3 class="mb-4 text-2xl font-bold text-sanctuary/80 uppercase tracking-widest">Décaissements</h3>
                    <div v-if="devise.decaissements.length === 0" class="text-sm text-graphite/62">Aucun décaissement ce mois-ci.</div>
                    <table v-else class="w-full text-sm">
                        <tbody>
                            <tr v-for="line in devise.decaissements" :key="line.account_code || line.account_label" class="border-b border-graphite/10 last:border-0">
                                <td v-if="devise.accountingStandardLabel" class="py-2 text-graphite/65">{{ line.account_code }}</td>
                                <td class="py-2 text-graphite/87">{{ line.account_label }}</td>
                                <td class="py-2 text-right font-medium text-graphite">{{ formatAmount(line.total, devise.currency) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>

            <p class="text-xs text-graphite/58">Ce rapport est calculé automatiquement depuis les mouvements enregistrés, jamais ressaisi séparément.</p>

            <!-- Note discrete (2026-09-11), uniquement visible a l'impression : le logo Oikonema est masque sur le rapport (voir AppLayout.vue), seule cette mention texte, tres petite, rappelle qui a genere le document. -->
            <p class="hidden print:block text-center text-[9px] text-graphite/40">Document généré par Oikonema</p>
        </div>
    </AppLayout>
</template>
