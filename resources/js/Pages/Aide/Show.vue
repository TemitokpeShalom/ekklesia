<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel. Le texte reçoit
 * des surcharges print:* pour rester lisible (noir sur blanc) une fois
 * imprimé, malgré l'habillage sombre a l'ecran.
 */
defineProps({
    article: Object,
    modules: Object,
})

function goBack() {
    window.history.back()
}
</script>

<template>
    <AppLayout>
        <template #actions>
            <button type="button" @click="window.print()" class="text-sm text-graphite/80 hover:text-graphite bg-graphite/5 hover:bg-graphite/10 border border-graphite/10 rounded-full px-4 py-2 transition-colors print:hidden">
                Imprimer
            </button>
            <button type="button" @click="goBack" class="text-sm text-graphite/80 hover:text-graphite bg-graphite/5 hover:bg-graphite/10 border border-graphite/10 rounded-full px-4 py-2 transition-colors print:hidden">
                Retour
            </button>
        </template>
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both] print:hidden">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    {{ article.module }}
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">{{ article.title }}</h1>
            </div>
        </template>

        <div class="max-w-5xl mx-auto grid md:grid-cols-[240px_minmax(0,1fr)] gap-10">
            <nav class="print:hidden">
                <Link href="/aide" class="text-sm font-medium text-sanctuary hover:text-gold">Manuel complet</Link>
                <div v-for="(items, moduleName) in modules" :key="moduleName" class="mt-5">
                    <h2 class="text-xs font-semibold text-graphite/62 uppercase tracking-widest mb-2">{{ moduleName }}</h2>
                    <ul class="space-y-1">
                        <li v-for="item in items" :key="item.slug">
                            <Link
                                :href="`/aide/${item.slug}`"
                                class="text-sm block"
                                :class="item.slug === article.slug ? 'text-graphite font-medium' : 'text-graphite/68 hover:text-graphite'"
                            >
                                {{ item.title }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>

            <article class="max-w-2xl glass-panel rounded-3xl p-6 print:bg-transparent print:border-0 print:p-0 animate-[fadeInUp_0.55s_ease-out_both]">
                <p v-for="(paragraph, i) in article.body.split('\n\n')" :key="i" class="text-sm text-graphite/80 mb-4 print:text-slate-700">
                    {{ paragraph }}
                </p>
            </article>
        </div>
    </AppLayout>
</template>
