<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { queueSignalement } from '@/offlineSignalementsQueue'

/**
 * Canal de signalement (point 17). Meme logique que la premiere version
 * (formulaire + liste, statut modifiable pour qui a le droit
 * "manageSignalements") mais habillee directement en v3 des sa creation --
 * jamais publiee en v2, pas de double travail.
 */
const props = defineProps({
  orgUnit: Object,
  signalements: Array,
  canManage: Boolean,
})

const localSignalements = ref(props.signalements.map((s) => ({ ...s })))

const form = useForm({
  category: '',
  message: '',
  is_anonymous: false,
})

const savedOffline = ref(false)

// Hors connexion (2026-11-01) : le signalement est garde sur l'appareil puis
// envoye automatiquement au retour du reseau (offlineSignalementsQueue.js).
function submit() {
  savedOffline.value = false

  if (typeof navigator !== 'undefined' && navigator.onLine === false) {
    queueSignalement(props.orgUnit.id, { ...form.data() })
    form.reset()
    savedOffline.value = true

    return
  }

  form.post(`/org-units/${props.orgUnit.id}/signalements`, {
    onSuccess: () => form.reset(),
  })
}

/**
 * Corrige le 2026-09-12 (audit demandé par le ministère sur ce module) :
 * ce changement de statut était "optimiste" (l'écran affichait le
 * nouveau statut avant même la réponse du serveur) mais sans aucun
 * filet - en cas d'échec (session expirée, droits perdus entre-temps,
 * etc.), l'écran continuait d'afficher le nouveau statut comme si tout
 * s'était bien passé, sans le moindre message d'erreur ni retour à
 * l'ancien statut. Corrigé : l'ancien statut est restauré si la requête
 * échoue, et `preserveScroll` évite de faire sauter la page en haut à
 * chaque changement (gênant en bas d'une longue liste de signalements).
 */
function onStatusChange(s, newStatus) {
  const previousStatus = s.status
  s.status = newStatus
  useForm({ status: newStatus }).put(`/org-units/${props.orgUnit.id}/signalements/${s.id}`, {
    preserveScroll: true,
    onError: () => {
      s.status = previousStatus
    },
  })
}

const STATUS_LABELS = {
  nouveau: 'Nouveau',
  en_cours: 'En cours',
  traite: 'Traité',
}

const STATUS_DOT = {
  nouveau: 'bg-gold',
  en_cours: 'bg-slateblue',
  traite: 'bg-forest',
}

function formatDate(iso) {
  return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}
</script>

<template>
  <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
    <template #title>
      <div class="animate-[fadeInUp_0.5s_ease-out_both]">
        <p class="text-xs uppercase tracking-widest text-azure/80 font-semibold flex items-center gap-2">
          <span class="inline-block w-6 h-px bg-azure/40"></span>
          Canal de signalement
        </p>
        <h1 class="font-serif text-5xl font-bold text-graphite mt-2">Une préoccupation à remonter ?</h1>
        <p class="text-sm text-graphite/70 mt-2 max-w-2xl">
          Visible depuis {{ orgUnit.name }} et tout niveau au-dessus, jamais ailleurs (exactement l'inverse du chemin suivi par les annonces).
        </p>
      </div>
    </template>

    <div class="max-w-2xl mx-auto space-y-10">
            <div v-if="savedOffline" class="mb-4 rounded-2xl border border-gold/40 bg-gold/10 px-4 py-3 text-sm text-graphite/87 flex items-start gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 flex-shrink-0 text-gold">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <span>Signalement enregistré sur cet appareil (pas de connexion pour le moment). Il sera envoyé automatiquement dès que la connexion revient, sans rien faire de plus.</span>
            </div>
      <form @submit.prevent="submit" class="glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
        <div class="mb-4">
          <label class="block text-xs font-semibold text-azure uppercase tracking-wide mb-1.5">Catégorie</label>
          <input v-model="form.category" type="text" maxlength="100" placeholder="Ex. : conflit, sécurité, finances..."
            class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
          <p v-if="form.errors.category" class="text-sm text-rose-600 mt-1">{{ form.errors.category }}</p>
        </div>

        <div class="mb-4">
          <label class="block text-xs font-semibold text-azure uppercase tracking-wide mb-1.5">Description</label>
          <textarea v-model="form.message" rows="4" maxlength="5000"
            class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
          <p v-if="form.errors.message" class="text-sm text-rose-600 mt-1">{{ form.errors.message }}</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-graphite/80 mb-5">
          <input v-model="form.is_anonymous" type="checkbox" class="rounded border-graphite/25 bg-graphite/5 text-gold focus:ring-gold/50" />
          Envoyer anonymement
        </label>

        <button type="submit" :disabled="form.processing"
          class="w-full bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl py-2.5 font-semibold shadow-lg shadow-azure/20 disabled:opacity-60">
          Envoyer le signalement
        </button>
      </form>

      <div>
        <h3 class="font-serif text-[2.5rem] font-bold text-graphite mb-4">
          {{ localSignalements.length ? 'Signalements reçus' : "Aucun signalement pour l'instant" }}
        </h3>

        <div class="space-y-4">
          <div v-for="s in localSignalements" :key="s.id" class="glass-panel rounded-2xl p-5">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs uppercase tracking-widest text-azure font-semibold">{{ s.category }}</span>
              <span class="text-xs text-graphite/62">{{ formatDate(s.created_at) }}</span>
            </div>
            <p class="text-sm text-graphite/90 mb-3">{{ s.message }}</p>
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-graphite/65">
              <span>
                {{ s.org_unit.name }} ({{ s.org_unit.level_label }}) ·
                {{ s.is_anonymous ? 'Anonyme' : (s.submitter_name || 'Membre') }}
              </span>
              <select v-if="canManage" :value="s.status" @change="onStatusChange(s, $event.target.value)"
                class="bg-graphite/5 border border-graphite/15 text-graphite rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-gold/50">
                <option v-for="(label, value) in STATUS_LABELS" :key="value" :value="value" class="bg-white text-graphite">{{ label }}</option>
              </select>
              <span v-else class="inline-flex items-center gap-1.5 font-medium text-graphite/87">
                <span class="w-1.5 h-1.5 rounded-full" :class="STATUS_DOT[s.status]"></span>
                {{ STATUS_LABELS[s.status] }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
