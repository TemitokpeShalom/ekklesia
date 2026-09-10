<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

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

function submit() {
  form.post(`/org-units/${props.orgUnit.id}/signalements`, {
    onSuccess: () => form.reset(),
  })
}

function onStatusChange(s, newStatus) {
  s.status = newStatus
  useForm({ status: newStatus }).put(`/org-units/${props.orgUnit.id}/signalements/${s.id}`)
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
        <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
          <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
          Canal de signalement
        </p>
        <h1 class="font-serif text-3xl text-graphite mt-2">Une préoccupation à remonter ?</h1>
        <p class="text-sm text-graphite/70 mt-2 max-w-2xl">
          Visible depuis {{ orgUnit.name }} et tout niveau au-dessus, jamais ailleurs (exactement l'inverse du chemin suivi par les annonces).
        </p>
      </div>
    </template>

    <div class="max-w-2xl mx-auto space-y-10">
      <form @submit.prevent="submit" class="glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
        <div class="mb-4">
          <label class="block text-xs font-semibold text-sanctuary/80 uppercase tracking-wide mb-1.5">Catégorie</label>
          <input v-model="form.category" type="text" maxlength="100" placeholder="Ex. : conflit, sécurité, finances..."
            class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
          <p v-if="form.errors.category" class="text-sm text-rose-600 mt-1">{{ form.errors.category }}</p>
        </div>

        <div class="mb-4">
          <label class="block text-xs font-semibold text-sanctuary/80 uppercase tracking-wide mb-1.5">Description</label>
          <textarea v-model="form.message" rows="4" maxlength="5000"
            class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
          <p v-if="form.errors.message" class="text-sm text-rose-600 mt-1">{{ form.errors.message }}</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-graphite/80 mb-5">
          <input v-model="form.is_anonymous" type="checkbox" class="rounded border-graphite/25 bg-graphite/5 text-gold focus:ring-gold/50" />
          Envoyer anonymement
        </label>

        <button type="submit" :disabled="form.processing"
          class="w-full bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-2.5 font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
          Envoyer le signalement
        </button>
      </form>

      <div>
        <h3 class="font-serif text-xl text-graphite mb-4">
          {{ localSignalements.length ? 'Signalements reçus' : "Aucun signalement pour l'instant" }}
        </h3>

        <div class="space-y-4">
          <div v-for="s in localSignalements" :key="s.id" class="glass-panel rounded-2xl p-5">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs uppercase tracking-widest text-sanctuary/90 font-semibold">{{ s.category }}</span>
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
