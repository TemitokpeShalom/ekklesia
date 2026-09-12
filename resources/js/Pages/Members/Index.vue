<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 *
 * Corrige le 2026-09-12 (retour du ministere : "il n'y a pas de champ de
 * recherche... pour voir si telle personne est reellement membre de notre
 * ministere et sur quelle eglise") : ajout d'une recherche qui porte sur ce
 * noeud ET tous ses descendants (voir MembersController::searchMembers),
 * jamais seulement la liste locale ci-dessous. Une recherche active
 * remplace la liste par les resultats (eglise locale + pasteur responsable
 * de chaque personne trouvee), pour rester lisible - la liste locale
 * habituelle revient des que le champ de recherche est vide.
 */
const props = defineProps({
    orgUnit: Object,
    members: Array,
    search: String,
    searchResults: Array,
})

const query = ref(props.search ?? '')

function submitSearch() {
    router.get(`/org-units/${props.orgUnit.id}/membres`, { q: query.value }, { preserveState: true, preserveScroll: true })
}

function clearSearch() {
    query.value = ''
    router.get(`/org-units/${props.orgUnit.id}/membres`, {}, { preserveState: true, preserveScroll: true })
}

function initials(person) {
    return `${person.first_name?.[0] ?? ''}${person.last_name?.[0] ?? ''}`.toUpperCase()
}

// Coordonnees "a portee de clic" (demande du ministere : "qu'on puisse le
// joindre facilement") : un numero affiche devient un vrai lien
// d'appel/SMS, jamais juste du texte a recopier a la main.
function telHref(phone) {
    return `tel:${phone.replace(/[^+\d]/g, '')}`
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Membres
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">{{ members.length }} membre{{ members.length > 1 ? 's' : '' }}</h1>
            </div>
        </template>

        <div class="space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between animate-[fadeInUp_0.5s_ease-out_both]">
                <!-- Corrige le 2026-09-12 : recherche par nom, telephone ou e-mail, etendue a toute la base du ministere sous ce noeud (voir MembersController). -->
                <form @submit.prevent="submitSearch" class="flex-1 flex gap-2 max-w-md">
                    <div class="relative flex-1">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-graphite/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input
                            v-model="query"
                            type="text"
                            placeholder="Rechercher un membre (nom, téléphone, e-mail)..."
                            class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/50 rounded-xl pl-10 pr-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-azure/40 focus:border-azure/60"
                        />
                    </div>
                    <button type="submit" class="flex-shrink-0 inline-flex items-center bg-graphite/5 hover:bg-graphite/10 border border-graphite/15 transition-colors text-graphite rounded-xl px-4 py-2.5 text-sm font-medium">
                        Rechercher
                    </button>
                    <button v-if="search" type="button" @click="clearSearch" class="flex-shrink-0 inline-flex items-center text-graphite/60 hover:text-graphite rounded-xl px-2 py-2.5 text-sm">
                        Effacer
                    </button>
                </form>

                <!-- Corrige le 2026-09-12 : couleur du bouton (or -> bleu), meme demande que sur les ecrans publics. -->
                <Link
                    :href="`/org-units/${orgUnit.id}/membres/nouveau`"
                    class="flex-shrink-0 inline-flex items-center justify-center gap-1.5 bg-gradient-to-r from-azure to-azure-dark hover:shadow-glow-azure transition-all duration-300 text-white rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-azure/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Ajouter un membre
                </Link>
            </div>

            <!-- Resultats de recherche (ce noeud + tous ses descendants) : remplace la liste locale tant qu'une recherche est active. -->
            <template v-if="search">
                <p class="text-sm text-graphite/62 animate-[fadeInUp_0.52s_ease-out_both]">
                    {{ searchResults.length }} résultat{{ searchResults.length > 1 ? 's' : '' }} pour « {{ search }} », dans {{ orgUnit.name }} et toutes ses entités rattachées.
                </p>

                <div v-if="searchResults.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center animate-[fadeInUp_0.55s_ease-out_both]">
                    <p class="text-sm font-medium text-graphite/80">Aucun membre ne correspond à cette recherche.</p>
                    <p class="mt-1 text-sm text-graphite/62">Cette personne n'est peut-être pas (encore) enregistrée dans ce ministère.</p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="(row, i) in searchResults"
                        :key="row.member.id"
                        class="glass-panel rounded-2xl p-5 animate-[fadeInUp_0.5s_ease-out_both]"
                        :style="{ animationDelay: `${Math.min(i, 10) * 40}ms` }"
                    >
                        <div class="flex items-start gap-4">
                            <img
                                v-if="row.member.photo_path"
                                :src="`/storage/${row.member.photo_path}`"
                                class="h-11 w-11 flex-shrink-0 rounded-full object-cover border border-graphite/15"
                            />
                            <span v-else class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-graphite/5 text-xs font-semibold text-graphite/73">
                                {{ initials(row.member) }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="truncate font-semibold text-graphite text-[15px]">
                                        <span v-if="row.member.title">{{ row.member.title }} </span>{{ row.member.first_name }} {{ row.member.last_name }}
                                    </p>
                                    <span
                                        class="flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="row.member.status === 'active' ? 'bg-forest/15 text-forest' : 'bg-graphite/10 text-graphite/68'"
                                    >
                                        {{ row.member.status === 'active' ? 'Actif' : 'Inactif' }}
                                    </span>
                                </div>
                                <p v-if="row.member.spouse_name" class="text-xs text-graphite/55 mt-0.5">Conjoint(e) : {{ row.member.spouse_name }}</p>

                                <!-- Coordonnees propres du membre, en priorite et cliquables. -->
                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                    <a v-if="row.member.phone" :href="telHref(row.member.phone)" class="inline-flex items-center gap-1.5 font-medium text-azure hover:underline">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a2.25 2.25 0 00-2.36.766l-.716.894a12 12 0 01-6.235-6.235l.894-.716a2.25 2.25 0 00.766-2.36L6.663 3.852A1.125 1.125 0 005.572 3H4.5a2.25 2.25 0 00-2.25 2.25z" /></svg>
                                        {{ row.member.phone }}
                                    </a>
                                    <a v-if="row.member.email" :href="`mailto:${row.member.email}`" class="text-graphite/70 hover:underline">{{ row.member.email }}</a>
                                    <span v-if="!row.member.phone && !row.member.email" class="text-graphite/50">Aucun contact renseigné</span>
                                </div>

                                <!-- Eglise locale + pasteur responsable : le coeur de la demande ("sur quelle eglise il est", "le nom de son pasteur et son numero"). -->
                                <div class="mt-3 pt-3 border-t border-graphite/10 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-graphite/65">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="font-semibold text-graphite/80">{{ row.org_unit.level_label }} :</span> {{ row.org_unit.name }}
                                    </span>
                                    <span v-if="row.pastor" class="inline-flex items-center gap-1.5">
                                        <span class="font-semibold text-graphite/80">Pasteur :</span> {{ row.pastor.name }}
                                        <a v-if="row.pastor.phone" :href="telHref(row.pastor.phone)" class="text-azure hover:underline font-medium">{{ row.pastor.phone }}</a>
                                    </span>
                                    <span v-else class="text-graphite/45">Aucun pasteur affecté à cette entité pour l'instant</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Liste locale habituelle (aucune recherche active) : inchangee dans son role, seules les coordonnees deviennent cliquables. -->
            <template v-else>
                <div v-if="members.length === 0" class="glass-panel flex flex-col items-center rounded-3xl px-8 py-14 text-center animate-[fadeInUp_0.55s_ease-out_both]">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-graphite/5 text-graphite/62">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </span>
                    <p class="mt-4 text-sm font-medium text-graphite/80">Aucun membre enregistré pour l'instant.</p>
                    <p class="mt-1 text-sm text-graphite/62">Ajoute un premier membre pour commencer.</p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="(member, i) in members"
                        :key="member.id"
                        class="flex items-center gap-4 glass-panel rounded-2xl p-5 hover:border-graphite/20 transition-all duration-300 animate-[fadeInUp_0.5s_ease-out_both]"
                        :style="{ animationDelay: `${Math.min(i, 10) * 40}ms` }"
                    >
                        <img
                            v-if="member.photo_path"
                            :src="`/storage/${member.photo_path}`"
                            class="h-10 w-10 flex-shrink-0 rounded-full object-cover border border-graphite/15"
                        />
                        <span
                            v-else
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-graphite/5 text-xs font-semibold text-graphite/73"
                        >
                            {{ initials(member) }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <p class="truncate font-semibold text-graphite text-[15px]">{{ member.first_name }} {{ member.last_name }}</p>
                                <span
                                    class="flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="member.status === 'active' ? 'bg-forest/15 text-forest' : 'bg-graphite/10 text-graphite/68'"
                                >
                                    {{ member.status === 'active' ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-graphite/65 flex flex-wrap items-center gap-x-3">
                                <a v-if="member.phone" :href="telHref(member.phone)" class="font-medium text-azure hover:underline">{{ member.phone }}</a>
                                <a v-if="member.email" :href="`mailto:${member.email}`" class="hover:underline">{{ member.email }}</a>
                                <span v-if="!member.phone && !member.email">Aucun contact renseigné</span>
                            </p>
                        </div>

                        <Link
                            :href="`/org-units/${orgUnit.id}/membres/${member.id}/modifier`"
                            class="flex-shrink-0 self-center rounded-lg px-3 py-1.5 text-sm font-medium text-graphite/68 transition hover:bg-graphite/10 hover:text-graphite"
                        >
                            Modifier
                        </Link>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
