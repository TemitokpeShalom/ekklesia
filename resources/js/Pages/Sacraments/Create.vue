<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Sacrements individuels (point 08), ecran construit directement dans le
 * style v3 "Vitrail". Le conjoint d'un mariage peut etre un membre deja
 * enregistre (liste deroulante) ou une personne non enregistree (simple
 * nom libre) : les deux champs restent facultatifs, l'un ou l'autre suffit.
 */
const props = defineProps({
    orgUnit: Object,
    members: Array,
})

const form = useForm({
    type: 'bapteme',
    member_id: '',
    member_name: '',
    spouse_member_id: '',
    spouse_name: '',
    event_date: '',
    officiant: '',
    location: '',
    notes: '',
})

function memberLabel(member) {
    return `${member.first_name} ${member.last_name}`
}

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/sacrements`)
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/sacrements`" back-label="Retour">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Sacrements
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">Enregistrer un sacrement</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <section>
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Nature du sacrement</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <label
                            class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-medium transition"
                            :class="form.type === 'bapteme' ? 'border-gold/60 bg-gold/10 text-sanctuary' : 'border-graphite/15 bg-graphite/5 text-graphite/73 hover:bg-graphite/10'"
                        >
                            <input v-model="form.type" type="radio" value="bapteme" class="sr-only" />
                            Baptême
                        </label>
                        <label
                            class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-medium transition"
                            :class="form.type === 'mariage' ? 'border-gold/60 bg-gold/10 text-sanctuary' : 'border-graphite/15 bg-graphite/5 text-graphite/73 hover:bg-graphite/10'"
                        >
                            <input v-model="form.type" type="radio" value="mariage" class="sr-only" />
                            Mariage
                        </label>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">
                        {{ form.type === 'mariage' ? 'Premier conjoint' : 'Membre concerné' }}
                    </h2>
                    <!-- Corrige le 2026-09-12 (retour du ministere) : la personne concernée n'est pas toujours déjà enregistrée sur la plateforme - même mécanisme que le second conjoint ci-dessous (membre OU nom libre). -->
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Si déjà membre enregistré</label>
                            <select v-model="form.member_id" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option value="" class="bg-white text-graphite">Aucun</option>
                                <option v-for="m in members" :key="m.id" :value="m.id" class="bg-white text-graphite">{{ memberLabel(m) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Sinon, nom complet</label>
                            <input v-model="form.member_name" type="text" placeholder="Non enregistré(e) sur cette plateforme" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                    </div>
                    <p v-if="form.errors.member_id" class="mt-1 text-sm text-rose-600">{{ form.errors.member_id }}</p>
                </section>

                <section v-if="form.type === 'mariage'" class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Second conjoint</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Si déjà membre enregistré</label>
                            <select v-model="form.spouse_member_id" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                                <option value="" class="bg-white text-graphite">Aucun</option>
                                <option v-for="m in members" :key="m.id" :value="m.id" class="bg-white text-graphite">{{ memberLabel(m) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Sinon, nom du conjoint ou de la conjointe</label>
                            <input v-model="form.spouse_name" type="text" placeholder="Non enregistré(e) sur cette plateforme" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Quand et où</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Date</label>
                            <input v-model="form.event_date" type="date" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                            <p v-if="form.errors.event_date" class="mt-1 text-sm text-rose-600">{{ form.errors.event_date }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Lieu</label>
                            <input v-model="form.location" type="text" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                    </div>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">Officiant et remarques</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Officiant</label>
                            <input v-model="form.officiant" type="text" placeholder="Pasteur..." class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-graphite/87">Notes</label>
                            <textarea v-model="form.notes" rows="3" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                        </div>
                    </div>
                </section>

                <div class="border-t border-graphite/10 pt-6">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20 disabled:opacity-60"
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
