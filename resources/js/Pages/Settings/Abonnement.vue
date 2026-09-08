<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
  orgUnit: Object,
  plans: Array,
  subscription: Object,
})

const form = useForm({
  plan_id: props.subscription.plan_id,
})

function choose(planId) {
  form.plan_id = planId
  form.put(`/org-units/${props.orgUnit.id}/abonnement`)
}

function formatPrice(price) {
  const n = Number(price)
  if (n === 0) return 'Gratuit'
  return new Intl.NumberFormat('fr-FR').format(n) + ' FCFA / mois'
}

function formatMembers(max) {
  return max ? `Jusqu'à ${max} membres` : 'Membres illimités'
}

function formatDate(iso) {
  if (!iso) return null
  return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}
</script>

<template>
  <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
    <template #title>
      <div class="animate-[fadeInUp_0.5s_ease-out_both]">
        <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
          <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
          Paramètres
        </p>
        <h1 class="font-serif text-3xl text-white mt-2">Abonnement et facturation</h1>
        <p class="text-sm text-white/55 mt-2 max-w-2xl">
          Choisissez l'offre adaptée à la taille de votre ministère. Le changement est appliqué immédiatement.
        </p>
      </div>
    </template>

    <div class="space-y-8">
      <div v-if="subscription.on_trial" class="glass-panel rounded-2xl px-5 py-4 flex items-center gap-3 border-gold/30 animate-[fadeInUp_0.55s_ease-out_both]">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gold-soft shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm text-white/80">
          Période d'essai en cours, jusqu'au <span class="font-semibold text-white">{{ formatDate(subscription.trial_ends_at) }}</span>.
        </p>
      </div>

      <div v-else-if="subscription.status === 'active'" class="glass-panel rounded-2xl px-5 py-4 border-forest/30 animate-[fadeInUp_0.55s_ease-out_both]">
        <p class="text-sm text-white/80">
          Abonnement actif : renouvellement le <span class="font-semibold text-white">{{ formatDate(subscription.current_period_ends_at) }}</span>.
        </p>
      </div>

      <div v-else class="glass-panel rounded-2xl px-5 py-4 border-sanctuary/30 animate-[fadeInUp_0.55s_ease-out_both]">
        <p class="text-sm text-sanctuary-light">
          Aucun abonnement actif. Choisissez une offre ci-dessous pour continuer à utiliser Ekklesia sans interruption.
        </p>
      </div>

      <div class="grid sm:grid-cols-3 gap-5 animate-[fadeInUp_0.6s_ease-out_both]">
        <div v-for="plan in plans" :key="plan.id"
          class="glass-panel rounded-3xl p-6 flex flex-col"
          :class="plan.id === subscription.plan_id ? 'border-forest/50 ring-1 ring-forest/30' : ''">
          <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold mb-1">{{ plan.name }}</p>
          <p class="font-serif text-2xl text-white mb-1">{{ formatPrice(plan.price_monthly) }}</p>
          <p class="text-sm text-white/45 mb-4">{{ formatMembers(plan.max_members) }}</p>

          <ul class="space-y-2 text-sm text-white/75 mb-6 flex-1">
            <li v-for="(feature, i) in plan.features" :key="i" class="flex items-start gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-forest shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ feature }}</span>
            </li>
          </ul>

          <button v-if="plan.id === subscription.plan_id" type="button" disabled
            class="w-full bg-forest/15 text-forest rounded-xl py-2.5 font-medium text-sm text-center">
            Plan actuel
          </button>
          <button v-else type="button" :disabled="form.processing" @click="choose(plan.id)"
            class="w-full inline-flex items-center justify-center bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-2.5 font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
            Choisir ce plan
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
