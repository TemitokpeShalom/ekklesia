<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({ summary: { type: Object, default: () => ({}) } })

const tuiles = [
    { href: '/technique/messagerie', label: 'Messagerie', desc: 'Lire et répondre à tout le monde', badge: 'messages_non_lus', color: 'from-azure to-azure-dark',
      icon: 'M8 10h8M8 14h5m-8 6l-3 2V6a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H7z' },
    { href: '/technique/reglages', label: 'Réglages', desc: 'Clés de l\'assistant et du paiement', color: 'from-gold to-gold-dark',
      icon: 'M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75' },
    { href: '/technique/signalements', label: 'Signalements', desc: 'Problèmes et idées remontés', badge: 'nouveaux_signalements', color: 'from-sanctuary to-sanctuary-dark',
      icon: 'M12 9v3.75m0 3.75h.007M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { href: '/technique/equipe', label: 'Équipe technique', desc: 'Qui a accès à cet espace', color: 'from-forest to-forest/70',
      icon: 'M15 19.13a9.4 9.4 0 002.62-.37 3 3 0 00-4.12-2.8M15 19.13V19a6 6 0 00-12 0v.13M15 19.13A12 12 0 019 20a12 12 0 01-6-1.87M12 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z' },
]
</script>

<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <Link v-for="t in tuiles" :key="t.href" :href="t.href"
            class="relative flex flex-col items-center text-center gap-2 rounded-2xl bg-white border border-graphite/10 shadow-card px-3 py-5 hover:border-azure/40 hover:-translate-y-0.5 transition">
            <span :class="t.color" class="w-14 h-14 rounded-2xl bg-gradient-to-br text-white flex items-center justify-center shadow-md">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" :d="t.icon" /></svg>
            </span>
            <span class="font-semibold text-graphite text-sm">{{ t.label }}</span>
            <span class="text-xs text-graphite/55 leading-snug">{{ t.desc }}</span>
            <span v-if="t.badge && summary[t.badge]" class="absolute top-2.5 right-2.5 min-w-[1.4rem] h-6 px-1.5 rounded-full bg-sanctuary text-white text-xs font-semibold flex items-center justify-center">{{ summary[t.badge] }}</span>
        </Link>
    </div>
</template>
