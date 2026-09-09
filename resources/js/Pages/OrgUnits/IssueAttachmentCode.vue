<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
})

const page = usePage()
const plainCode = computed(() => page.props.flash?.plain_code)
// Inclut le code en parametre : la page de rattachement le pre-remplit
// (voir RedeemAttachmentCode.vue / prefillCode), pour eviter un
// copier-coller manuel a la personne qui va l'utiliser.
const redeemUrl = computed(() => `${window.location.origin}/rattachement?code=${encodeURIComponent(plainCode.value ?? '')}`)

const LEVEL_NAMES = ['Ministère', 'Continent', 'Pays', 'Région', 'District', 'Église locale', 'Cellule']

const availableRanks = computed(() =>
    LEVEL_NAMES
        .map((label, rank) => ({ rank, label }))
        .filter(({ rank }) => rank > props.orgUnit.level_rank)
)

const form = useForm({
    target_level_rank: '',
    valid_for_hours: 72,
})

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/code-de-rattachement`, {
        preserveScroll: true,
    })
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Rattachement
                </p>
                <h1 class="font-serif text-3xl text-white mt-2">Émettre un code de rattachement</h1>
            </div>
        </template>

        <div class="max-w-lg mx-auto space-y-6">
            <div v-if="plainCode" class="glass-panel rounded-2xl p-5 border-forest/40 animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="mb-2 text-sm font-medium text-forest">Code généré. Transmettez-le à la personne qui va créer la nouvelle entité :</p>
                <p class="rounded-xl border border-white/15 bg-white/5 px-3.5 py-2.5 text-center text-2xl font-mono tracking-widest text-white">{{ plainCode }}</p>
                <p class="mt-2 text-xs text-white/40">Ce code ne peut être utilisé qu'une seule fois et expire après la durée choisie.</p>
                <a :href="redeemUrl" target="_blank"
                    class="mt-4 inline-flex w-full items-center justify-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20">
                    Ouvrir la page de rattachement (code pré-rempli)
                </a>
                <p class="mt-2 text-xs text-white/40">Ou transmettez ce lien à la personne concernée, si elle doit le faire elle-même.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <div>
                    <label class="mb-1 block text-sm font-medium text-white/80">Niveau de la nouvelle entité</label>
                    <select v-model="form.target_level_rank" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                        <option value="" disabled class="bg-night text-white">Choisir un niveau</option>
                        <option v-for="opt in availableRanks" :key="opt.rank" :value="opt.rank" class="bg-night text-white">{{ opt.label }}</option>
                    </select>
                    <p v-if="form.errors.target_level_rank" class="mt-1 text-sm text-rose-400">{{ form.errors.target_level_rank }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-white/80">Durée de validité (heures)</label>
                    <input v-model.number="form.valid_for_hours" type="number" min="1" max="720" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p class="mt-1 text-xs text-white/35">72 heures par défaut, 720 heures (30 jours) maximum.</p>
                    <p v-if="form.errors.valid_for_hours" class="mt-1 text-sm text-rose-400">{{ form.errors.valid_for_hours }}</p>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60"
                    >
                        Générer le code
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
