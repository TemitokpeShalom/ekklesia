<script setup>
import { useForm, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09), puis point 15 (passerelle de paiement,
 * 10/09/2026) : la mise a jour directe d'origine (form/choose) reste le
 * traitement d'un paiement recu hors ligne (virement, especes). FedaPay et
 * crypto s'y ajoutent comme deux moyens de paiement en ligne verifies,
 * chacun affiche seulement s'il est reellement configure sur ce serveur
 * (voir payment.fedapay_available / payment.crypto_wallet_address).
 */
const props = defineProps({
  orgUnit: Object,
  plans: Array,
  subscription: Object,
  payment: Object,
  paymentHistory: Array,
})

const page = usePage()
// Point 15 : le controleur flashe 'error' quand FedaPay ou la verification
// crypto echoue (voir SubscriptionFedapayController/SubscriptionCryptoController)
// - jusqu'ici jamais affiche nulle part sur cette page, l'administrateur ne
// voyait donc aucun message et ne pouvait s'en apercevoir qu'en relisant
// l'historique des paiements plus bas.
const flashError = computed(() => page.props.flash?.error)

const form = useForm({
  plan_id: props.subscription.plan_id,
})

function choose(planId) {
  form.plan_id = planId
  form.put(`/org-units/${props.orgUnit.id}/abonnement`)
}

function payWithFedapay(planId) {
  router.post(`/org-units/${props.orgUnit.id}/abonnement/fedapay`, { plan_id: planId })
}

const cryptoOpenFor = ref(null)
const cryptoForm = useForm({
  plan_id: '',
  tx_hash: '',
})

function openCrypto(planId) {
  cryptoOpenFor.value = planId
  cryptoForm.plan_id = planId
  cryptoForm.tx_hash = ''
}

function submitCrypto() {
  cryptoForm.post(`/org-units/${props.orgUnit.id}/abonnement/crypto`, {
    preserveScroll: true,
    onSuccess: () => { cryptoOpenFor.value = null },
  })
}

function formatPrice(price, currency) {
  const n = Number(price)
  if (n === 0) return 'Gratuit'
  return new Intl.NumberFormat('fr-FR').format(n) + ' ' + (currency || 'FCFA') + ' / mois'
}

function formatMembers(max) {
  return max ? `Jusqu'à ${max} membres` : 'Membres illimités'
}

function formatDate(iso) {
  if (!iso) return null
  return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}

function providerLabel(provider) {
  return { fedapay: 'FedaPay', crypto: 'Crypto (USDT/BNB)' }[provider] ?? provider
}

function statusLabel(status) {
  return { pending: 'En attente', success: 'Réussi', failed: 'Échoué' }[status] ?? status
}

// Point 15 (08/09/2026) : conversion XOF -> USDT calculee cote serveur
// (voir SubscriptionController::edit(), ExchangeRateService) - jamais
// recalculee ici, uniquement mise en forme pour l'affichage.
function formatUsdt(value) {
  return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value)
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
      <div v-if="flashError" class="glass-panel rounded-2xl px-5 py-4 border-rose-400/40 animate-[fadeInUp_0.5s_ease-out_both]">
        <p class="text-sm text-rose-300">{{ flashError }}</p>
      </div>

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
          <p class="font-serif text-2xl text-white mb-1">{{ formatPrice(plan.price_monthly, plan.currency) }}</p>
          <p class="text-sm text-white/45 mb-4">{{ formatMembers(plan.max_members) }}</p>

          <ul class="space-y-2 text-sm text-white/75 mb-6 flex-1">
            <li v-for="(feature, i) in plan.features" :key="i" class="flex items-start gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-forest shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ feature }}</span>
            </li>
          </ul>

          <div v-if="plan.id === subscription.plan_id" class="space-y-2">
            <button type="button" disabled
              class="w-full bg-forest/15 text-forest rounded-xl py-2.5 font-medium text-sm text-center">
              Plan actuel
            </button>
          </div>
          <div v-else class="space-y-2">
            <button v-if="payment.fedapay_available && Number(plan.price_monthly) > 0" type="button"
              @click="payWithFedapay(plan.id)"
              class="w-full inline-flex items-center justify-center bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-2.5 font-semibold shadow-lg shadow-gold/20">
              Payer avec FedaPay
            </button>
            <button v-if="payment.crypto_wallet_address && Number(plan.price_monthly) > 0" type="button"
              @click="openCrypto(plan.id)"
              class="w-full glass-panel-light rounded-xl py-2.5 font-medium text-sm text-white/80 hover:border-gold/40 hover:text-gold-soft transition">
              <template v-if="plan.usdt_estimate != null">Payer en crypto (≈ {{ formatUsdt(plan.usdt_estimate) }} USDT)</template>
              <template v-else>Payer en crypto (USDT/BNB)</template>
            </button>
            <button type="button" :disabled="form.processing" @click="choose(plan.id)"
              class="w-full rounded-xl py-2.5 text-sm font-medium text-white/50 transition hover:bg-white/10 hover:text-white">
              Marquer comme payé hors ligne
            </button>

            <div v-if="cryptoOpenFor === plan.id" class="mt-3 space-y-3 rounded-xl border border-white/10 bg-white/5 p-4">
              <p v-if="plan.usdt_estimate != null" class="text-sm text-gold-soft font-semibold">
                Montant à envoyer : ≈ {{ formatUsdt(plan.usdt_estimate) }} USDT (ou l'équivalent en BNB au cours du jour)
              </p>
              <p class="text-xs text-white/55">
                Envoyez ce montant en USDT (BEP-20) ou BNB, sur BNB Smart Chain, à l'adresse :
                <span class="block mt-1 break-all font-mono text-white/80">{{ payment.crypto_wallet_address }}</span>
                Puis collez ici le hash de la transaction pour vérification. Le montant reçu est vérifié automatiquement par rapport au prix du plan (une petite marge est tolérée en cas de légère variation du cours entre l'affichage et l'envoi).
              </p>
              <input v-model="cryptoForm.tx_hash" type="text" placeholder="0x..."
                class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 text-sm font-mono transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
              <p v-if="cryptoForm.errors.tx_hash" class="text-sm text-rose-400">{{ cryptoForm.errors.tx_hash }}</p>
              <div class="flex items-center gap-2">
                <button type="button" :disabled="cryptoForm.processing || !cryptoForm.tx_hash" @click="submitCrypto"
                  class="flex-1 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-2 text-sm font-semibold disabled:opacity-60">
                  Vérifier et activer
                </button>
                <button type="button" @click="cryptoOpenFor = null" class="rounded-xl px-3 py-2 text-sm text-white/50 hover:text-white">
                  Annuler
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <section v-if="paymentHistory.length > 0" class="glass-panel rounded-3xl p-6 animate-[fadeInUp_0.65s_ease-out_both]">
        <h3 class="mb-4 text-xs font-semibold text-gold-soft/80 uppercase tracking-widest">Historique des paiements</h3>
        <table class="w-full text-sm">
          <tbody>
            <tr v-for="entry in paymentHistory" :key="entry.id" class="border-b border-white/10 last:border-0">
              <td class="py-2 text-white/45">{{ formatDate(entry.created_at) }}</td>
              <td class="py-2 text-white/80">{{ entry.plan?.name ?? '-' }}</td>
              <td class="py-2 text-white/60">{{ providerLabel(entry.provider) }}</td>
              <td class="py-2 text-right text-white/80">{{ entry.amount ? formatPrice(entry.amount, entry.currency) : '-' }}</td>
              <td class="py-2 text-right" :class="entry.status === 'success' ? 'text-forest' : entry.status === 'failed' ? 'text-rose-400' : 'text-gold-soft'">
                {{ statusLabel(entry.status) }}
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  </AppLayout>
</template>
