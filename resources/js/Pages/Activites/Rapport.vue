<script setup>
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
    month: String,
    report: Object,
    cultes: Array,
    canManage: Boolean,
})

const form = useForm({
    month: props.month,
    baptisms_count: props.report?.baptisms_count ?? '',
    new_converts_count: props.report?.new_converts_count ?? '',
    activities_notes: props.report?.activities_notes ?? '',
    remarks: props.report?.remarks ?? '',
    leader_notes: props.report?.leader_notes ?? '',
})

function monthLabel(value) {
    const [year, month] = value.split('-')
    return new Date(year, month - 1, 1).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
}

function dateLabel(value) {
    const [year, month, day] = value.split('-')
    return new Date(year, month - 1, day).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function changeMonth(event) {
    router.get(`/org-units/${props.orgUnit.id}/rapport-activites`, { mois: event.target.value })
}

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/rapport-activites`)
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/finances`" back-label="Retour aux finances">
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
            <div class="flex justify-end animate-[fadeInUp_0.5s_ease-out_both]">
                <input
                    type="month"
                    :value="month"
                    @change="changeMonth"
                    class="bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"
                />
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
                <h3 class="mb-4 text-xs font-semibold text-sanctuary/80 uppercase tracking-widest">Cultes tenus ce mois</h3>
                <p class="text-xl font-serif text-graphite">{{ cultes.length }}</p>
                <p class="mt-4 text-xs text-graphite/58">Le détail (date, présences) de chaque culte est ci-dessous. Aucun total d'effectifs n'est calculé : une même personne présente à plusieurs cultes du mois y serait comptée plusieurs fois.</p>
            </section>

            <section class="glass-panel rounded-3xl p-6 animate-[fadeInUp_0.57s_ease-out_both]">
                <h3 class="mb-4 text-xs font-semibold text-sanctuary/80 uppercase tracking-widest">Détail par culte</h3>
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
                <fieldset :disabled="!canManage" class="space-y-8">
                    <section>
                        <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest mb-4">Baptêmes et nouveaux convertis</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-graphite/87">Baptêmes</label>
                                <input v-model="form.baptisms_count" type="number" min="0" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-graphite/87">Nouveaux convertis</label>
                                <input v-model="form.new_converts_count" type="number" min="0" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            </div>
                        </div>
                    </section>

                    <section class="border-t border-graphite/10 pt-6">
                        <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest mb-4">Activités du mois</h2>
                        <textarea v-model="form.activities_notes" rows="3" placeholder="Évangélisations, réveils, événements particuliers..." class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                    </section>

                    <section class="border-t border-graphite/10 pt-6">
                        <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest mb-4">Remarques et suggestions</h2>
                        <textarea v-model="form.remarks" rows="3" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                    </section>

                    <section class="border-t border-graphite/10 pt-6">
                        <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest mb-4">Situation du responsable</h2>
                        <textarea v-model="form.leader_notes" rows="3" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                        <p class="mt-1 text-xs text-graphite/58">Réservé à la hiérarchie pastorale directe, jamais visible dans une consolidation générale.</p>
                    </section>
                </fieldset>

                <div v-if="canManage" class="border-t border-graphite/10 pt-6">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
