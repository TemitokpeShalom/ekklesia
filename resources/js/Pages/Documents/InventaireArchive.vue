<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import MinistryLetterhead from '@/Components/MinistryLetterhead.vue'

/**
 * Document d'archive - fiche d'inventaire annuelle validée (chantier
 * "module Inventaire", 2026-09-12, retour du ministere : "a la fin de
 * chaque annee, on nous demande souvent de faire la liste des inventaires
 * et d'envoyer... un document sous forme de rapport... qui doit aussi
 * porter l'entete du ministere"). Meme convention que les archives
 * Finances/Activités : lecture seule, entête + bloc position, puis le
 * contenu (ici la liste détaillée des biens).
 *
 * Amelioration par rapport au modele papier fourni par le ministere
 * (INVENTAIRE KETOU 2026.docx) : la colonne "CODE D'IDENTIFICATION",
 * toujours vide a la main, est ici deja remplie - chaque bien recoit son
 * code automatiquement et definitivement des sa creation (voir
 * AssetsController::nextCode), jamais ressaisi. Les lignes de signature
 * (Pasteur / Contrôleur) du modele original sont conservées, pour un
 * usage identique une fois le document imprimé.
 */
const props = defineProps({
    orgUnit: Object,
    ministry: Object,
    ancestry: Array,
    pastorName: String,
    year: Number,
    parCategorie: Object,
    currency: String,
    validation: Object,
})

const categories = [
    { key: 'immobilier', label: 'Biens immobiliers' },
    { key: 'mobilier', label: 'Biens mobiliers' },
]

function groupFor(key) {
    return props.parCategorie?.[key] ?? { items: [], total: 0 }
}

const totalGeneral = computed(() => categories.reduce((sum, c) => sum + (groupFor(c.key).total || 0), 0))

// Numerotation sequentielle continue sur l'ensemble du document (N°),
// comme sur le modele papier du ministere - pas remise a zero par
// categorie. Precalculee dans une Map (jamais un compteur mute pendant le
// rendu du template, qui peut s'executer plusieurs fois et fausser la
// numerotation).
const sequence = computed(() => {
    const map = new Map()
    let n = 0
    for (const cat of categories) {
        for (const item of groupFor(cat.key).items) {
            n += 1
            map.set(item.id, n)
        }
    }
    return map
})

function numero(item) {
    return sequence.value.get(item.id)
}

function provenanceLabel(value) {
    return {
        don: 'Don',
        achat_caisse: 'Achat sur caisse',
        achat_offrande: 'Achat sur offrande',
        subvention: 'Subvention',
        legs: 'Legs',
        construction: 'Construction',
    }[value] ?? value
}

function conditionLabel(value) {
    return {
        fonctionnel: 'Fonctionnel',
        a_surveiller: 'À surveiller',
        hors_service: 'Hors service',
    }[value] ?? value
}

function formatAmount(value) {
    return new Intl.NumberFormat('fr-FR').format(value || 0) + ' ' + props.currency
}

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function validatedLabel(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function print() {
    window.print()
}
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
                    <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold">Fiche d'inventaire</p>
                    <h2 class="font-serif text-5xl font-bold text-ink mt-1">Exercice {{ year }} — au 31 décembre {{ year }}</h2>

                    <p class="text-sm text-coffee-light mt-3">{{ orgUnit.name }} <span class="text-xs">({{ orgUnit.level_label }})</span></p>
                    <p v-if="ancestry.length" class="text-xs text-coffee-light/80 mt-1">
                        <span v-for="(a, i) in ancestry" :key="i">{{ a.name }}<span v-if="i < ancestry.length - 1"> › </span></span>
                    </p>
                    <p v-if="pastorName" class="text-xs text-coffee-light/80 mt-0.5">Pasteur : {{ pastorName }}</p>
                    <p class="text-[11px] text-coffee-light/70 mt-2">Vue consolidée de cette entité et de tous ses niveaux descendants.</p>
                </div>

                <div class="border-t border-coffee/10 pt-6 mb-6">
                    <p class="text-xs uppercase tracking-widest text-coffee-light">Valeur totale du patrimoine</p>
                    <p class="mt-1 font-serif text-xl text-ink">{{ formatAmount(totalGeneral) }}</p>
                </div>

                <div v-for="cat in categories" :key="cat.key" class="border-t border-coffee/10 pt-6 mb-6">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-2xl font-bold text-azure uppercase tracking-widest">{{ cat.label }}</h3>
                        <p class="text-sm font-semibold text-ink">{{ formatAmount(groupFor(cat.key).total) }}</p>
                    </div>
                    <div v-if="groupFor(cat.key).items.length === 0" class="text-sm text-coffee-light">Aucun bien dans cette catégorie.</div>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-coffee/10 text-left text-[11px] uppercase tracking-widest text-coffee-light/80">
                                <th class="py-1.5 pr-2 font-semibold">N°</th>
                                <th class="py-1.5 pr-2 font-semibold">Désignation</th>
                                <th class="py-1.5 pr-2 font-semibold text-right">Qté</th>
                                <th class="py-1.5 pr-2 font-semibold">Acquis le</th>
                                <th class="py-1.5 pr-2 font-semibold">État</th>
                                <th class="py-1.5 pr-2 font-semibold">Code d'identification</th>
                                <th class="py-1.5 pr-2 font-semibold">Observation</th>
                                <th class="py-1.5 font-semibold text-right">Montant brut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in groupFor(cat.key).items" :key="item.id" class="border-b border-coffee/10 last:border-0 align-top">
                                <td class="py-1.5 pr-2 text-coffee-light">{{ numero(item) }}</td>
                                <td class="py-1.5 pr-2 text-ink">
                                    {{ item.label }}
                                    <span class="text-coffee-light">×{{ item.quantity }}</span>
                                    <div class="text-[11px] text-coffee-light/70">{{ item.org_unit?.name }} · {{ provenanceLabel(item.provenance) }}</div>
                                </td>
                                <td class="py-1.5 pr-2 text-right text-ink">{{ item.quantity }}</td>
                                <td class="py-1.5 pr-2 text-coffee-light whitespace-nowrap">{{ formatDate(item.acquisition_date) }}</td>
                                <td class="py-1.5 pr-2 text-coffee-light">{{ conditionLabel(item.condition) }}</td>
                                <td class="py-1.5 pr-2 font-mono text-[11px] text-coffee-light">{{ item.code }}</td>
                                <td class="py-1.5 pr-2 text-coffee-light">{{ item.observation || '—' }}</td>
                                <td class="py-1.5 text-right font-medium text-ink whitespace-nowrap">{{ formatAmount(item.acquisition_value) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-coffee/10 pt-10 mt-10 grid grid-cols-2 gap-8 text-sm text-center">
                    <div>
                        <p class="text-xs uppercase tracking-widest text-coffee-light">Pasteur</p>
                        <p class="mt-8 border-t border-coffee/30 pt-1 text-[11px] text-coffee-light/70">Signature</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-widest text-coffee-light">Contrôleur</p>
                        <p class="mt-8 border-t border-coffee/30 pt-1 text-[11px] text-coffee-light/70">Signature</p>
                    </div>
                </div>

                <p class="border-t border-coffee/10 pt-4 mt-8 text-xs text-coffee-light/80 text-center">
                    Fiche validée et archivée le {{ validatedLabel(validation.validated_at) }}<span v-if="validation.validator_name"> par {{ validation.validator_name }}</span>.
                </p>
                <p class="hidden print:block text-center text-[9px] text-coffee-light/50 mt-1">Document généré par Oikonema</p>
            </div>
        </main>
    </div>
</template>
