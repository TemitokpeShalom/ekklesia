<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Equipes et benevolat (point 08), ecran construit directement dans le
 * style v3 "Vitrail". Combine deux formulaires distincts sur un seul
 * ecran : les informations de l'equipe, et la composition (ajout/retrait
 * d'un membre), geree par TeamMembersController sans ecran dedie separe.
 */
const props = defineProps({
    orgUnit: Object,
    equipe: Object,
    members: Array,
})

const form = useForm({
    name: props.equipe.name,
    description: props.equipe.description ?? '',
})

const addForm = useForm({
    member_id: '',
    role_in_team: '',
    joined_at: '',
})

const availableMembers = computed(() => {
    const already = new Set(props.equipe.team_members.map((tm) => tm.member_id))
    return props.members.filter((m) => !already.has(m.id))
})

function memberLabel(member) {
    return `${member.first_name} ${member.last_name}`
}

function formatDate(value) {
    if (!value) return null
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
}

function submit() {
    form.put(`/org-units/${props.orgUnit.id}/equipes/${props.equipe.id}`)
}

function destroy() {
    if (confirm('Supprimer cette équipe ? Tous les membres qui y sont affectés en seront retirés. Cette action est irréversible.')) {
        router.delete(`/org-units/${props.orgUnit.id}/equipes/${props.equipe.id}`)
    }
}

function addMember() {
    addForm.post(`/org-units/${props.orgUnit.id}/equipes/${props.equipe.id}/membres`, {
        preserveScroll: true,
        onSuccess: () => addForm.reset(),
    })
}

function removeMember(teamMember) {
    if (confirm(`Retirer ${memberLabel(teamMember.member)} de cette équipe ?`)) {
        router.delete(`/org-units/${props.orgUnit.id}/equipes/${props.equipe.id}/membres/${teamMember.id}`, {
            preserveScroll: true,
        })
    }
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/equipes`" back-label="Annuler">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Équipes et bénévolat
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">{{ equipe.name }}</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto space-y-8">
            <form @submit.prevent="submit" class="space-y-8 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <section>
                    <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest mb-4">Nom de l'équipe</h2>
                    <input v-model="form.name" type="text" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p>
                </section>

                <section class="border-t border-graphite/10 pt-6">
                    <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest mb-4">Description (optionnel)</h2>
                    <textarea v-model="form.description" rows="3" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"></textarea>
                </section>

                <div class="flex items-center justify-between border-t border-graphite/10 pt-6">
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
                    <button type="button" @click="destroy" class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-medium text-rose-600 transition hover:bg-rose-500/10">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        Supprimer l'équipe
                    </button>
                </div>
            </form>

            <section class="glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.6s_ease-out_both]">
                <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest mb-4">
                    Membres de l'équipe ({{ equipe.team_members.length }})
                </h2>

                <div v-if="equipe.team_members.length === 0" class="text-sm text-graphite/62">
                    Aucun membre affecté pour l'instant.
                </div>
                <div v-else class="space-y-2 mb-6">
                    <div
                        v-for="tm in equipe.team_members"
                        :key="tm.id"
                        class="flex items-center justify-between gap-3 rounded-xl bg-graphite/5 border border-graphite/10 px-4 py-2.5"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-graphite">{{ memberLabel(tm.member) }}</p>
                            <p class="text-xs text-graphite/65">
                                <span v-if="tm.role_in_team">{{ tm.role_in_team }}</span>
                                <span v-if="tm.role_in_team && tm.joined_at"> · </span>
                                <span v-if="tm.joined_at">Depuis le {{ formatDate(tm.joined_at) }}</span>
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="removeMember(tm)"
                            class="flex-shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium text-rose-600 transition hover:bg-rose-500/10"
                        >
                            Retirer
                        </button>
                    </div>
                </div>

                <form @submit.prevent="addMember" class="border-t border-graphite/10 pt-5 space-y-4">
                    <h3 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest">Ajouter un membre</h3>
                    <div>
                        <select v-model="addForm.member_id" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                            <option value="" class="bg-white text-graphite">Sélectionner un membre</option>
                            <option v-for="m in availableMembers" :key="m.id" :value="m.id" class="bg-white text-graphite">{{ memberLabel(m) }}</option>
                        </select>
                        <p v-if="addForm.errors.member_id" class="mt-1 text-sm text-rose-600">{{ addForm.errors.member_id }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <input v-model="addForm.role_in_team" type="text" placeholder="Rôle (responsable...)" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                        <div>
                            <input v-model="addForm.joined_at" type="date" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        </div>
                    </div>
                    <button
                        type="submit"
                        :disabled="addForm.processing || !addForm.member_id"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Ajouter
                    </button>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
