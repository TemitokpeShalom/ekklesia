<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Assistant (point 17) : recherche par mots-clés dans les articles d'aide
 * (voir AssistantController::search) -- pas de LLM branche, une reponse
 * franche a ce sujet reste dans /aide si l'utilisateur cherche plus loin.
 * Route independante d'un org_unit, d'ou AppLayout sans org-unit ici.
 */
const props = defineProps({
  query: String,
  results: Array,
})

const question = ref('')
const history = ref([])

if (props.query) {
  history.value.push({ question: props.query, results: props.results })
}

function ask() {
  const q = question.value.trim()
  if (!q) return

  router.get('/assistant', { q }, {
    preserveState: true,
    preserveScroll: true,
    only: ['query', 'results'],
    onSuccess: (page) => {
      history.value.push({ question: q, results: page.props.results })
      question.value = ''
    },
  })
}

const suggestions = [
  'Comment inviter un titulaire ?',
  'Comment créer un code de rattachement ?',
  'Comment enregistrer une dîme ?',
]

function askSuggestion(text) {
  question.value = text
  ask()
}
</script>

<template>
  <AppLayout back-href="/aide" back-label="Manuel complet">
    <template #title>
      <div class="animate-[fadeInUp_0.5s_ease-out_both]">
        <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
          <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
          Documentation
        </p>
        <h1 class="font-serif text-3xl text-white mt-2">Assistant</h1>
        <p class="text-sm text-white/55 mt-2 max-w-xl">
          Recherche dans le manuel d'utilisation à partir de vos mots-clés : aucune connexion externe, tout reste dans l'application.
        </p>
      </div>
    </template>

    <div class="max-w-2xl mx-auto">
      <form @submit.prevent="ask" class="flex items-center gap-2 mb-8 animate-[fadeInUp_0.55s_ease-out_both]">
        <input v-model="question" type="text" placeholder="Ex. : comment inviter un titulaire ?"
          class="flex-1 bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
        <button type="submit"
          class="shrink-0 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20">
          Demander
        </button>
      </form>

      <div v-if="!history.length" class="mb-8">
        <p class="text-xs uppercase tracking-widest text-white/40 font-semibold mb-3">Exemples</p>
        <div class="flex flex-wrap gap-2">
          <button v-for="s in suggestions" :key="s" type="button" @click="askSuggestion(s)"
            class="text-sm glass-panel-light rounded-full px-4 py-2 hover:border-gold/40 hover:text-gold-soft transition">
            {{ s }}
          </button>
        </div>
      </div>

      <div class="space-y-8">
        <div v-for="(exchange, i) in history" :key="i">
          <p class="text-sm text-white/85 glass-panel-light rounded-2xl px-4 py-2.5 inline-block mb-3">
            {{ exchange.question }}
          </p>

          <div v-if="exchange.results.length" class="space-y-3">
            <a v-for="r in exchange.results" :key="r.slug" :href="`/aide/${r.slug}`"
              class="block glass-panel rounded-2xl p-5 hover:border-gold/30 hover:shadow-glow-gold transition-all duration-300">
              <p class="text-xs uppercase tracking-widest text-gold-soft/90 font-semibold mb-1">{{ r.module }}</p>
              <p class="font-semibold text-white text-[15px] mb-1">{{ r.title }}</p>
              <p class="text-sm text-white/50">{{ r.excerpt }}</p>
            </a>
          </div>
          <p v-else class="text-sm text-white/45">
            Aucun article ne correspond à cette question. Essayez d'autres mots-clés, ou consultez le
            <Link href="/aide" class="text-gold-soft hover:underline">manuel complet</Link>.
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
