<script setup>
import { useForm, Link } from '@inertiajs/vue3'

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
  <div class="min-h-screen bg-parchment">
    <header class="border-b border-coffee/10 bg-white px-6 py-5 flex items-center justify-between">
      <div>
        <p class="text-xs uppercase tracking-widest text-gold-dark font-semibold">{{ orgUnit.level_label }}</p>
        <h1 class="font-serif text-xl text-ink">{{ orgUnit.name }}</h1>
      </div>
      <Link :href="`/org-units/${orgUnit.id}`" class="text-sm text-coffee-light hover:text-ink transition">Retour au tableau de bord</Link>
    </header>

    <main class="max-w-4xl mx-auto px-6 py-10">
      <h2 class="font-serif text-2xl text-ink mb-1">Abonnement et facturation</h2>
      <p class="text-sm text-coffee-light mb-6">
        Choisissez l'offre adaptée à la taille de votre ministère. Le changement est appliqué immédiatement.
      </p>

      <div v-if="subscription.on_trial" class="mb-8 bg-gold/10 border border-gold/30 rounded-2xl px-5 py-4 flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gold-dark shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm text-ink">
          Période d'essai en cours, jusqu'au <span class="font-semibold">{{ formatDate(subscription.trial_ends_at) }}</span>.
        </p>
      </div>

      <div v-else-if="subscription.status === 'active'" class="mb-8 bg-sanctuary/5 border border-sanctuary/20 rounded-2xl px-5 py-4">
        <p class="text-sm text-ink">
          Abonnement actif — renouvellement le <span class="font-semibold">{{ formatDate(subscription.current_period_ends_at) }}</span>.
        </p>
      </div>

      <div v-else class="mb-8 bg-red-50 border border-red-200 rounded-2xl px-5 py-4">
        <p class="text-sm text-red-700">
          Aucun abonnement actif. Choisissez une offre ci-dessous pour continuer à utiliser Ekklesia sans interruption.
        </p>
      </div>

      <div class="grid sm:grid-cols-3 gap-5">
        <div v-for="plan in plans" :key="plan.id"
          class="bg-white border rounded-3xl shadow-sm p-6 flex flex-col"
          :class="plan.id === subscription.plan_id ? 'border-sanctuary ring-2 ring-sanctuary/30' : 'border-coffee/10'">
          <p class="text-xs uppercase tracking-widest text-gold-dark font-semibold mb-1">{{ plan.name }}</p>
          <p class="font-serif text-2xl text-ink mb-1">{{ formatPrice(plan.price_monthly) }}</p>
          <p class="text-sm text-coffee-light mb-4">{{ formatMembers(plan.max_members) }}</p>

          <ul class="space-y-2 text-sm text-ink mb-6 flex-1">
            <li v-for="(feature, i) in plan.features" :key="i" class="flex items-start gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sanctuary shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ feature }}</span>
            </li>
          </ul>

          <button v-if="plan.id === subscription.plan_id" type="button" disabled
            class="w-full bg-sanctuary/10 text-sanctuary-dark rounded-xl py-2.5 font-medium text-sm text-center">
            Plan actuel
          </button>
          <button v-else type="button" :disabled="form.processing" @click="choose(plan.id)"
            class="w-full bg-gradient-to-r from-sanctuary to-sanctuary-dark hover:from-sanctuary-dark hover:to-sanctuary-dark transition-all duration-300 text-white rounded-xl py-2.5 font-medium shadow-lg shadow-sanctuary/30 disabled:opacity-60">
            Choisir ce plan
          </button>
        </div>
      </div>
    </main>
  </div>
</template>
