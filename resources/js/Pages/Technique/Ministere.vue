<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({ ministry: Object, users: Array, payments: Array })

function formatDate(iso) {
    return iso ? new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : ''
}

function nouveauMotDePasse(u) {
    if (confirm(`Créer un nouveau mot de passe provisoire pour ${u.name} ? L'ancien ne fonctionnera plus.`)) {
        router.post(`/technique/utilisateurs/${u.id}/mot-de-passe`, {}, { preserveScroll: true })
    }
}
</script>

<template>
    <AppLayout back-href="/technique" back-label="Espace technique">
        <template #title>
            <p class="text-[11px] uppercase tracking-wide text-sanctuary/80 font-semibold">Ministère</p>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-graphite mt-1">{{ ministry.name }}</h1>
            <p class="text-sm text-graphite/60 mt-1">Créé le {{ formatDate(ministry.created_at) }}</p>
        </template>

        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="rounded-2xl bg-white border border-graphite/10 shadow-card px-4 py-3"><p class="text-2xl font-serif text-graphite">{{ ministry.org_units_count }}</p><p class="text-xs text-graphite/60">Entités</p></div>
            <div class="rounded-2xl bg-white border border-graphite/10 shadow-card px-4 py-3"><p class="text-2xl font-serif text-graphite">{{ ministry.members_count }}</p><p class="text-xs text-graphite/60">Membres</p></div>
            <div class="rounded-2xl bg-white border border-graphite/10 shadow-card px-4 py-3"><p class="text-2xl font-serif text-graphite">{{ users.length }}</p><p class="text-xs text-graphite/60">Personnes avec accès</p></div>
        </div>

        <section class="rounded-2xl bg-white border border-graphite/10 shadow-card p-5 mb-6">
            <h2 class="font-serif text-lg font-semibold text-graphite mb-3">Personnes avec accès</h2>
            <div v-for="u in users" :key="u.id" class="flex flex-wrap items-center gap-3 py-3 border-t border-graphite/5 first:border-t-0">
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-graphite">{{ u.name }}</p>
                    <p class="text-xs text-graphite/55">{{ u.email }}<span v-if="u.phone"> · {{ u.phone }}</span></p>
                    <p class="text-xs text-graphite/65 mt-0.5">{{ u.roles.join(' ; ') }}</p>
                </div>
                <button type="button" class="text-sm text-azure hover:underline font-medium" @click="nouveauMotDePasse(u)">Nouveau mot de passe</button>
            </div>
            <p v-if="!users.length" class="text-sm text-graphite/55">Aucune personne.</p>
        </section>

        <section class="rounded-2xl bg-white border border-graphite/10 shadow-card p-5">
            <h2 class="font-serif text-lg font-semibold text-graphite mb-3">Derniers paiements</h2>
            <div v-for="p in payments" :key="p.id" class="flex items-center gap-3 py-2 border-t border-graphite/5 first:border-t-0 text-sm">
                <span class="flex-1 text-graphite/80">{{ formatDate(p.created_at) }} · {{ p.method }}</span>
                <span class="text-graphite/70">{{ p.amount }} {{ p.currency }}</span>
                <span class="text-xs rounded-full bg-graphite/5 px-2 py-0.5">{{ p.status }}</span>
            </div>
            <p v-if="!payments.length" class="text-sm text-graphite/55">Aucun paiement.</p>
        </section>
    </AppLayout>
</template>
