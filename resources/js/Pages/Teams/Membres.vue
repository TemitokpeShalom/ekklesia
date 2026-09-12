<script setup>
import { useForm, router, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Equipes et benevolat (point 08) : composition d'une equipe (ajout/retrait
 * de membres). Corrige le 2026-09-12 (retour du ministere) : cet ecran est
 * desormais separe de "Modifier" (nom/description de l'equipe, voir
 * Teams/Edit.vue) et s'ouvre en cliquant directement sur l'equipe depuis la
 * liste - "je ne veux pas dire qu'on va supprimer le menu modifier... mais
 * pour enregistrer un membre, quand on clique sur n'importe où sur la
 * ligne... ça affiche en même temps la page pour enregistrer un membre".
 */
const props = defineProps({
    orgUnit: Object,
    equipe: Object,
    members: Array,
})

const addForm = useForm({
    member_id: '',
    new_member_name: '',
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
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/equipes`" back-label="Retour">
        <template #actions>
            <Link
                :href="`/org-units/${orgUnit.id}/equipes/${equipe.id}/modifier`"
                class="text-sm text-graphite/80 hover:text-graphite bg-graphite/5 hover:bg-graphite/10 border border-graphite/10 rounded-full px-4 py-2 transition-colors"
            >
                Modifier l'équipe
            </Link>
        </template>
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Équipes et bénévolat
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">{{ equipe.name }}</h1>
                <p v-if="equipe.description" class="text-sm text-graphite/70 mt-2 max-w-2xl">{{ equipe.description }}</p>
            </div>
        </template>

        <div class="max-w-2xl mx-auto space-y-8">
            <section class="glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <h2 class="text-2xl font-bold text-azure uppercase tracking-widest mb-4">
                    Membres de l'équipe ({{ equipe.team_members.length }})
                </h2>

                <div v-if="equipe.team_members.length === 0" class="text-sm text-graphite/62">
                    Aucun membre affecté pour l'instant.
                </div>
                <div v-else class="space-y-2">
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
            </section>

            <form @submit.prevent="addMember" class="space-y-6 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.6s_ease-out_both]">
                <h2 class="text-2xl font-bold text-azure uppercase tracking-widest">Ajouter un membre</h2>

                <!-- Corrige le 2026-09-12 (retour du ministere) : "si elle n'est pas dans la base, qu'on puisse enregistrer directement... que ça crée en même temps dans les membres" - même mécanisme que Parcours de disciple : un nom saisi sans sélection crée une fiche membre minimale, complétable ensuite depuis le module Membres. -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Si déjà membre enregistré</label>
                    <select v-model="addForm.member_id" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                        <option value="" class="bg-white text-graphite">Aucun</option>
                        <option v-for="m in availableMembers" :key="m.id" :value="m.id" class="bg-white text-graphite">{{ memberLabel(m) }}</option>
                    </select>
                    <p v-if="addForm.errors.member_id" class="mt-1 text-sm text-rose-600">{{ addForm.errors.member_id }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Sinon, nom complet</label>
                    <input v-model="addForm.new_member_name" type="text" placeholder="Non enregistré(e) sur cette plateforme" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p class="mt-1 text-xs text-graphite/55">Une fiche membre simplifiée sera créée automatiquement avec ce nom.</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Rôle dans l'équipe (optionnel)</label>
                    <input v-model="addForm.role_in_team" type="text" placeholder="Responsable, trésorier, chantre..." class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Date d'entrée (optionnel)</label>
                    <input v-model="addForm.joined_at" type="date" class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                </div>

                <button
                    type="submit"
                    :disabled="addForm.processing"
                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20 disabled:opacity-60"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Ajouter
                </button>
            </form>
        </div>
    </AppLayout>
</template>
