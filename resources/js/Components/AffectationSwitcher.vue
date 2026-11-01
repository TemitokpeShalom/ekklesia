<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'

/**
 * Selecteur de role (2026-11-01). Remplace la rangee d'etiquettes "Mes
 * affectations actives" : une personne peut avoir plusieurs affectations
 * (pasteur du ministere, pasteur d'une eglise, comptable...) et la rangee
 * s'allongeait sans fin. Ici : UNE seule ligne compacte qui indique le role
 * exerce sur cet ecran, et une liste deroulante (defilante si longue) pour
 * passer d'un role a un autre. Chaque ligne reste un vrai lien vers le
 * tableau de bord de l'entite concernee, comme avant. Aucune donnee ni droit
 * d'acces modifie : memes affectations, meme destination.
 */
const props = defineProps({
    affectations: { type: Array, default: () => [] },
    currentOrgUnitId: { type: String, default: null },
})

const open = ref(false)
const many = computed(() => props.affectations.length > 1)
const current = computed(() => props.affectations.find(a => a.org_unit.id === props.currentOrgUnitId) || null)
</script>

<template>
    <div v-if="affectations.length" class="relative inline-block max-w-full">
        <component :is="many ? 'button' : 'div'" :type="many ? 'button' : undefined"
            @click="many && (open = !open)"
            class="flex items-center gap-3 max-w-full rounded-2xl bg-white border border-graphite/10 shadow-card px-3.5 py-2.5 text-left transition-colors"
            :class="many ? 'hover:border-azure/40 cursor-pointer' : ''">
            <span class="shrink-0 w-9 h-9 rounded-xl bg-sanctuary/10 text-sanctuary flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0" /></svg>
            </span>
            <span class="min-w-0">
                <span class="block text-[11px] uppercase tracking-wide text-graphite/55 font-semibold">{{ current ? 'Mon rôle ici' : 'Mes affectations' }}</span>
                <span v-if="current" class="block text-sm truncate">
                    <span class="font-semibold text-sanctuary">{{ current.role.label }}</span>
                    <span class="text-graphite/65">, {{ current.org_unit.name }}</span>
                </span>
                <span v-else class="block text-sm text-graphite/75">{{ affectations.length }} rôle{{ affectations.length > 1 ? 's' : '' }} actif{{ affectations.length > 1 ? 's' : '' }}</span>
            </span>
            <span v-if="many" class="shrink-0 ml-1 flex items-center gap-1.5">
                <span class="text-[11px] font-semibold text-azure bg-azure/10 rounded-full px-2 py-0.5">{{ affectations.length }}</span>
                <svg class="w-4 h-4 text-graphite/50 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </span>
        </component>

        <button v-if="open" type="button" tabindex="-1" aria-hidden="true" @click="open = false" class="fixed inset-0 z-10 cursor-default"></button>

        <div v-if="open" class="absolute left-0 mt-2 z-20 w-[min(22rem,calc(100vw-3rem))] rounded-2xl border border-graphite/10 bg-white shadow-card-hover py-2 animate-[fadeInUp_0.15s_ease-out_both]">
            <p class="px-4 pt-1 pb-1.5 text-[11px] uppercase tracking-wide text-graphite/55 font-semibold">Passer à un autre rôle</p>
            <div class="max-h-72 overflow-y-auto">
                <Link v-for="a in affectations" :key="a.id" :href="`/org-units/${a.org_unit.id}`"
                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-graphite/5" @click="open = false">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-sanctuary truncate">{{ a.role.label }}</span>
                        <span class="block text-xs text-graphite/65 truncate">{{ a.org_unit.name }}</span>
                    </span>
                    <svg v-if="a.org_unit.id === currentOrgUnitId" class="w-4 h-4 text-forest shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                </Link>
            </div>
        </div>
    </div>
</template>
