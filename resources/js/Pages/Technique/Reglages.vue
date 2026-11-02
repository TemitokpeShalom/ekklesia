<script setup>
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Reglages de la plateforme : clé de l'assistant, paiements, contact.
 * Les secrets ne s'affichent jamais en entier : on voit seulement les
 * 4 derniers caracteres. Laisser un champ vide = ne rien changer.
 */
const props = defineProps({ fields: Array })

const form = useForm({ values: Object.fromEntries(props.fields.map(f => [f.key, ''])), clear: [] })

function enregistrer() {
    form.post('/technique/reglages', { preserveScroll: true, onSuccess: () => { form.reset(); props.fields.forEach(f => (form.values[f.key] = '')) } })
}

function effacer(f) {
    if (confirm(`Retirer la valeur enregistrée sur le site pour : ${f.label} ? (La valeur du serveur, si elle existe, reprendra.)`)) {
        router.post('/technique/reglages', { clear: [f.key] }, { preserveScroll: true })
    }
}

function tester() {
    router.post('/technique/reglages/tester-assistant', {}, { preserveScroll: true })
}

const groupes = [
    { titre: 'Assistant Frère David', cles: ['anthropic_api_key', 'anthropic_model'] },
    { titre: 'Paiement FedaPay', cles: ['fedapay_environment', 'fedapay_public_key', 'fedapay_secret_key', 'fedapay_webhook_secret'] },
    { titre: 'Paiement en crypto', cles: ['crypto_wallet_address'] },
    { titre: 'Contact', cles: ['contact_email'] },
]
const champ = (k) => props.fields.find(f => f.key === k)
</script>

<template>
    <AppLayout back-href="/technique" back-label="Espace technique">
        <template #title>
            <p class="text-[11px] uppercase tracking-wide text-sanctuary/80 font-semibold">Espace technique</p>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-graphite mt-1">Réglages</h1>
            <p class="text-sm text-graphite/65 mt-1">Collez une nouvelle valeur seulement pour ce que vous voulez changer. Un champ laissé vide ne change rien.</p>
        </template>

        <form class="space-y-5" @submit.prevent="enregistrer">
            <section v-for="g in groupes" :key="g.titre" class="rounded-2xl bg-white border border-graphite/10 shadow-card p-5">
                <div class="flex items-center gap-3 mb-4">
                    <h2 class="font-serif text-lg font-semibold text-graphite flex-1">{{ g.titre }}</h2>
                    <button v-if="g.titre.startsWith('Assistant')" type="button" class="text-sm text-azure font-medium hover:underline" @click="tester">Tester la clé</button>
                </div>
                <div class="space-y-4">
                    <div v-for="k in g.cles" :key="k">
                        <label class="block text-sm font-medium text-graphite/85 mb-1">{{ champ(k).label }}</label>
                        <div class="flex items-center gap-2 text-xs mb-1.5">
                            <span v-if="champ(k).configured" class="inline-flex items-center rounded-full bg-forest/10 text-forest px-2 py-0.5 font-medium">Enregistré{{ champ(k).from_site ? ' sur le site' : ' sur le serveur' }}</span>
                            <span v-else class="inline-flex items-center rounded-full bg-rose-50 text-rose-700 px-2 py-0.5 font-medium">Manquant</span>
                            <span v-if="champ(k).preview" class="text-graphite/50 font-mono">{{ champ(k).preview }}</span>
                            <button v-if="champ(k).from_site" type="button" class="text-rose-600 hover:underline ml-auto" @click="effacer(champ(k))">Retirer</button>
                        </div>
                        <select v-if="k === 'fedapay_environment'" v-model="form.values[k]" class="w-full rounded-xl border border-graphite/15 px-3 py-2 text-sm">
                            <option value="">Ne pas changer</option>
                            <option value="sandbox">sandbox (essais, sans argent réel)</option>
                            <option value="live">live (vrais paiements)</option>
                        </select>
                        <input v-else v-model="form.values[k]" :type="champ(k).secret ? 'password' : 'text'" autocomplete="off" placeholder="Coller la nouvelle valeur ici"
                            class="w-full rounded-xl border border-graphite/15 px-3 py-2 text-sm" />
                    </div>
                </div>
            </section>
            <button type="submit" :disabled="form.processing" class="h-11 px-8 rounded-full bg-gradient-to-r from-azure to-azure-dark text-white font-semibold disabled:opacity-60">Enregistrer</button>
        </form>
    </AppLayout>
</template>
