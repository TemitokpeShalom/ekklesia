<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, avec un seul changement fonctionnel - la barre de
 * recherche ci-dessous. Elle remplace l'ancien ecran separe "Assistant"
 * (anciennement /assistant), qui n'etait qu'une deuxieme facon de
 * chercher dans ce meme manuel : deux entrees de menu pour un seul
 * contenu (voir l'audit du 2026-09-09, point 1). Desormais "Aide" est le
 * seul acces, recherche comprise - voir HelpController. Le texte reçoit
 * par ailleurs des surcharges print:* pour rester lisible (noir sur
 * blanc) une fois imprimé, malgré l'habillage sombre a l'ecran.
 */
const props = defineProps({
    modules: Object,
    query: { type: String, default: '' },
    results: { type: Array, default: () => [] },
})

const form = useForm({ q: props.query })

function search() {
    form.get('/aide', { preserveState: true, preserveScroll: true, only: ['query', 'results'] })
}

function goBack() {
    window.history.back()
}

function moduleSlug(name) {
    return name
        .toLowerCase()
        .replace(/é|è|ê/g, 'e')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '')
}
</script>

<template>
    <AppLayout>
        <template #actions>
            <button type="button" @click="window.print()" class="text-sm text-graphite/80 hover:text-graphite bg-graphite/5 hover:bg-graphite/10 border border-graphite/10 rounded-full px-4 py-2 transition-colors print:hidden">
                Imprimer / exporter en PDF
            </button>
            <button type="button" @click="goBack" class="text-sm text-graphite/80 hover:text-graphite bg-graphite/5 hover:bg-graphite/10 border border-graphite/10 rounded-full px-4 py-2 transition-colors print:hidden">
                Retour
            </button>
        </template>
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both] print:hidden">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Documentation
                </p>
                <h1 class="font-serif text-6xl font-bold text-graphite mt-2">Manuel d'utilisation</h1>
            </div>
        </template>

        <div class="max-w-3xl mx-auto print:max-w-none">
            <p class="text-sm text-graphite/70 mb-8 print:hidden">
                Ce manuel grandit avec l'application : chaque écran livré reçoit sa page d'aide au moment où il est
                construit, à partir du code réellement écrit. Le même contenu est consultable ici en entier, et
                depuis le lien "Aide" de chaque écran concerné.
            </p>

            <form @submit.prevent="search" class="mb-8 print:hidden flex gap-2">
                <input
                    v-model="form.q"
                    type="search"
                    placeholder="Chercher un mot-clé dans le manuel (ex. « rattachement », « finances »)"
                    class="flex-1 bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/58 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60"
                />
                <button type="submit" :disabled="form.processing"
                    class="shrink-0 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold disabled:opacity-60">
                    Chercher
                </button>
            </form>

            <section v-if="query" class="mb-10 glass-panel rounded-3xl p-6 print:hidden animate-[fadeInUp_0.5s_ease-out_both]">
                <h2 class="text-2xl font-bold text-sanctuary/80 uppercase tracking-widest mb-3">
                    Résultats pour « {{ query }} »
                </h2>
                <p v-if="!results.length" class="text-sm text-graphite/70">
                    Aucun article ne correspond. Le sommaire complet reste ci-dessous.
                </p>
                <ul v-else class="space-y-4">
                    <li v-for="r in results" :key="r.slug">
                        <a :href="`/aide/${r.slug}`" class="block group">
                            <p class="text-xs uppercase tracking-widest text-sanctuary/70 mb-0.5">{{ r.module }}</p>
                            <p class="text-sm font-semibold text-graphite group-hover:text-sanctuary">{{ r.title }}</p>
                            <p class="text-xs text-graphite/68 mt-0.5">{{ r.excerpt }}</p>
                        </a>
                    </li>
                </ul>
            </section>

            <nav class="mb-10 glass-panel rounded-3xl p-6 print:hidden animate-[fadeInUp_0.55s_ease-out_both]">
                <h2 class="text-2xl font-bold text-sanctuary/80 uppercase tracking-widest mb-3">Sommaire</h2>
                <ul class="grid sm:grid-cols-2 gap-x-6 gap-y-1">
                    <li v-for="(items, moduleName) in modules" :key="moduleName">
                        <a :href="`#${moduleSlug(moduleName)}`" class="text-sm text-graphite/80 hover:text-sanctuary">{{ moduleName }}</a>
                    </li>
                </ul>
            </nav>

            <section
                v-for="(items, moduleName) in modules"
                :key="moduleName"
                :id="moduleSlug(moduleName)"
                class="mb-10 break-inside-avoid-page glass-panel rounded-3xl p-6 print:bg-transparent print:border-0 print:p-0 animate-[fadeInUp_0.5s_ease-out_both]"
            >
                <h2 class="text-[2rem] font-bold text-graphite border-b border-graphite/10 pb-2 mb-4 print:text-black print:border-slate-300">{{ moduleName }}</h2>
                <article v-for="item in items" :key="item.slug" class="mb-8 break-inside-avoid-page">
                    <h3 class="text-[1.75rem] font-bold text-graphite/90 mb-2 print:text-black">{{ item.title }}</h3>
                    <p v-for="(paragraph, i) in item.body.split('\n\n')" :key="i" class="text-sm text-graphite/70 mb-2 print:text-slate-700">
                        {{ paragraph }}
                    </p>
                </article>
            </section>
        </div>
    </AppLayout>
</template>
