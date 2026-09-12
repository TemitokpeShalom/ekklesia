<script setup>
import { Link } from '@inertiajs/vue3'
import MinistryLetterhead from '@/Components/MinistryLetterhead.vue'
import DonutChart from '@/Components/DonutChart.vue'

const PALETTE = ['#003080', '#2f6b4f', '#6e1f35', '#b98a3e', '#2e4c6d', '#7a6152']

function breakdownSegments(lines) {
    return lines.map((line, i) => ({
        label: line.account_label,
        value: line.total,
        color: PALETTE[i % PALETTE.length],
    }))
}

/**
 * Document d'archive - rapport financier validé (chantier "Documents",
 * 2026-09-12). Meme contenu que le rapport financier "vivant"
 * (Finances/Rapport.vue, memes chiffres via FinanceReportBuilder), mais en
 * lecture seule et fige a l'instant de la validation - un mois valide ne
 * doit plus bouger si des ecritures sont corrigees ensuite, sans quoi
 * l'archive ne serait plus fidele au document remis.
 */
defineProps({
    orgUnit: Object,
    ministry: Object,
    ancestry: Array,
    pastorName: String,
    period: String,
    monthLabel: String,
    devises: Array,
    validation: Object,
})

function print() {
    window.print()
}

function formatAmount(value, currency) {
    return new Intl.NumberFormat('fr-FR').format(value) + ' ' + currency
}

function validatedLabel(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function formatLedgerDate(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
    <div class="min-h-screen bg-night print:bg-white">
        <header class="border-b border-white/10 glass-panel px-6 py-5 flex items-center justify-between print:hidden">
            <div>
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold">{{ orgUnit.level_label }}</p>
                <h1 class="font-serif text-xl text-white">{{ orgUnit.name }}</h1>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <Link :href="`/org-units/${orgUnit.id}/documents/rapports`" class="text-white/60 hover:text-white transition">Retour aux rapports</Link>
                <button @click="print"
                    class="rounded-full bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night px-5 py-2 text-sm font-semibold shadow-md shadow-gold/20">
                    Imprimer / Télécharger
                </button>
            </nav>
        </header>

        <main class="max-w-3xl mx-auto px-6 py-10 print:max-w-none print:px-0 print:py-0">
            <div class="bg-white border border-coffee/10 rounded-3xl print:rounded-none print:border-0 p-8 md:p-10 print:min-h-screen">
                <MinistryLetterhead :ministry="ministry" />

                <div class="text-center mb-8">
                    <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold">Rapport financier</p>
                    <h2 class="font-serif text-2xl text-ink mt-1 capitalize">{{ monthLabel }}</h2>

                    <p class="text-sm text-coffee-light mt-3">{{ orgUnit.name }} <span class="text-xs">({{ orgUnit.level_label }})</span></p>
                    <p v-if="ancestry.length" class="text-xs text-coffee-light/80 mt-1">
                        <span v-for="(a, i) in ancestry" :key="i">{{ a.name }}<span v-if="i < ancestry.length - 1"> › </span></span>
                    </p>
                    <p v-if="pastorName" class="text-xs text-coffee-light/80 mt-0.5">Pasteur : {{ pastorName }}</p>
                    <p class="text-[11px] text-coffee-light/70 mt-2">Vue consolidée de cette entité et de tous ses niveaux descendants.</p>
                </div>

                <div v-if="devises.length === 0" class="text-center text-sm text-coffee-light py-8">Aucun mouvement enregistré ce mois-ci, sur ce niveau ou ses descendants.</div>

                <div v-for="devise in devises" :key="devise.currency" class="border-t border-coffee/10 pt-6 mb-6 space-y-5">
                    <h3 class="text-sm font-semibold text-sanctuary/90 uppercase tracking-widest">{{ devise.currency }}</h3>

                    <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-5">
                        <div>
                            <p class="text-[11px] uppercase tracking-widest text-coffee-light">Ouverture</p>
                            <p class="font-serif text-base text-ink">{{ formatAmount(devise.openingBalance, devise.currency) }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-widest text-forest/90">Encaissements</p>
                            <p class="font-serif text-base text-ink">{{ formatAmount(devise.totalEncaissements, devise.currency) }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-widest text-sanctuary-light">Décaissements</p>
                            <p class="font-serif text-base text-ink">{{ formatAmount(devise.totalDecaissements, devise.currency) }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-widest text-coffee-light">Solde du mois</p>
                            <p class="font-serif text-base text-ink">{{ formatAmount(devise.solde, devise.currency) }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-widest text-azure">Clôture</p>
                            <p class="font-serif text-base text-ink">{{ formatAmount(devise.closingBalance, devise.currency) }}</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-2 text-xs font-semibold text-azure uppercase tracking-widest">Répartition</h4>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 print:grid-cols-3">
                            <div>
                                <p class="mb-1.5 text-[11px] text-coffee-light">Encaissements / décaissements</p>
                                <DonutChart :segments="[
                                    { label: 'Encaissements', value: devise.totalEncaissements, color: '#2f6b4f' },
                                    { label: 'Décaissements', value: devise.totalDecaissements, color: '#8f3049' },
                                ]" :size="120" />
                            </div>
                            <div>
                                <p class="mb-1.5 text-[11px] text-coffee-light">Détail des encaissements</p>
                                <DonutChart :segments="breakdownSegments(devise.encaissements)" :size="120" />
                            </div>
                            <div>
                                <p class="mb-1.5 text-[11px] text-coffee-light">Détail des décaissements</p>
                                <DonutChart :segments="breakdownSegments(devise.decaissements)" :size="120" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-2 text-xs font-semibold text-azure uppercase tracking-widest">Journal du mois, jour par jour</h4>
                        <div v-if="devise.ledger.length === 0" class="text-sm text-coffee-light">Aucun mouvement ce mois-ci.</div>
                        <table v-else class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[11px] uppercase tracking-widest text-coffee-light/80 border-b border-coffee/10">
                                    <th class="py-1.5 font-semibold">Date</th>
                                    <th class="py-1.5 font-semibold">Nature</th>
                                    <th class="py-1.5 font-semibold">Détail</th>
                                    <th class="py-1.5 font-semibold text-right">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="line in devise.ledger" :key="line.id" class="border-b border-coffee/10 last:border-0">
                                    <td class="py-1.5 text-coffee-light whitespace-nowrap">{{ formatLedgerDate(line.date) }}</td>
                                    <td class="py-1.5 text-ink">
                                        {{ line.type_label }}
                                        <span v-if="line.account_code" class="text-coffee-light">({{ line.account_code }})</span>
                                    </td>
                                    <td class="py-1.5 text-coffee-light">
                                        {{ line.org_unit_name }}<span v-if="line.counterparty"> · {{ line.counterparty }}</span>
                                    </td>
                                    <td class="py-1.5 text-right font-medium text-ink">
                                        {{ line.nature === 'encaissement' ? '+' : '-' }}{{ formatAmount(line.amount, devise.currency) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <h4 class="mb-2 text-xs font-semibold text-sanctuary/80 uppercase tracking-widest">Encaissements</h4>
                        <div v-if="devise.encaissements.length === 0" class="text-sm text-coffee-light">Aucun encaissement ce mois-ci.</div>
                        <table v-else class="w-full text-sm">
                            <tbody>
                                <tr v-for="line in devise.encaissements" :key="line.account_code || line.account_label" class="border-b border-coffee/10 last:border-0">
                                    <td v-if="devise.accountingStandardLabel" class="py-1.5 text-coffee-light">{{ line.account_code }}</td>
                                    <td class="py-1.5 text-ink">{{ line.account_label }}</td>
                                    <td class="py-1.5 text-right font-medium text-ink">{{ formatAmount(line.total, devise.currency) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <h4 class="mb-2 text-xs font-semibold text-sanctuary/80 uppercase tracking-widest">Décaissements</h4>
                        <div v-if="devise.decaissements.length === 0" class="text-sm text-coffee-light">Aucun décaissement ce mois-ci.</div>
                        <table v-else class="w-full text-sm">
                            <tbody>
                                <tr v-for="line in devise.decaissements" :key="line.account_code || line.account_label" class="border-b border-coffee/10 last:border-0">
                                    <td v-if="devise.accountingStandardLabel" class="py-1.5 text-coffee-light">{{ line.account_code }}</td>
                                    <td class="py-1.5 text-ink">{{ line.account_label }}</td>
                                    <td class="py-1.5 text-right font-medium text-ink">{{ formatAmount(line.total, devise.currency) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <p class="border-t border-coffee/10 pt-4 mt-8 text-xs text-coffee-light/80 text-center">
                    Rapport validé et archivé le {{ validatedLabel(validation.validated_at) }}<span v-if="validation.validator_name"> par {{ validation.validator_name }}</span>.
                </p>
                <p class="hidden print:block text-center text-[9px] text-coffee-light/50 mt-1">Document généré par Oikonema</p>
            </div>
        </main>
    </div>
</template>
