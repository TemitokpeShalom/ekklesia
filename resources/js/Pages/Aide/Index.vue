<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel. Le texte reçoit
 * des surcharges print:* pour rester lisible (noir sur blanc) une fois
 * imprimé, malgré l'habillage sombre a l'ecran.
 */
defineProps({
    modules: Object,
})

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
            <button type="button" @click="window.print()" class="text-sm text-white/70 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 rounded-full px-4 py-2 transition-colors print:hidden">
                Imprimer / exporter en PDF
            </button>
            <button type="button" @click="goBack" class="text-sm text-white/70 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 rounded-full px-4 py-2 transition-colors print:hidden">
                Retour
            </button>
        </template>
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both] print:hidden">
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Documentation
                </p>
                <h1 class="font-serif text-3xl text-white mt-2">Manuel d'utilisation</h1>
            </div>
        </template>

        <div class="max-w-3xl mx-auto print:max-w-none">
            <p class="text-sm text-white/55 mb-8 print:hidden">
                Ce manuel grandit avec l'application : chaque écran livré reçoit sa page d'aide au moment où il est
                construit, à partir du code réellement écrit. Le même contenu est consultable ici en entier, et
                depuis le lien "Aide" de chaque écran concerné.
            </p>

            <nav class="mb-10 glass-panel rounded-3xl p-6 print:hidden animate-[fadeInUp_0.55s_ease-out_both]">
                <h2 class="text-xs font-semibold text-gold-soft/80 uppercase tracking-widest mb-3">Sommaire</h2>
                <ul class="grid sm:grid-cols-2 gap-x-6 gap-y-1">
                    <li v-for="(items, moduleName) in modules" :key="moduleName">
                        <a :href="`#${moduleSlug(moduleName)}`" class="text-sm text-white/70 hover:text-gold-soft">{{ moduleName }}</a>
                    </li>
                </ul>
            </nav>

            <section
                v-for="(items, moduleName) in modules"
                :key="moduleName"
                :id="moduleSlug(moduleName)"
                class="mb-10 break-inside-avoid-page glass-panel rounded-3xl p-6 print:bg-transparent print:border-0 print:p-0 animate-[fadeInUp_0.5s_ease-out_both]"
            >
                <h2 class="text-base font-semibold text-white border-b border-white/10 pb-2 mb-4 print:text-black print:border-slate-300">{{ moduleName }}</h2>
                <article v-for="item in items" :key="item.slug" class="mb-8 break-inside-avoid-page">
                    <h3 class="text-sm font-semibold text-white/85 mb-2 print:text-black">{{ item.title }}</h3>
                    <p v-for="(paragraph, i) in item.body.split('\n\n')" :key="i" class="text-sm text-white/55 mb-2 print:text-slate-700">
                        {{ paragraph }}
                    </p>
                </article>
            </section>
        </div>
    </AppLayout>
</template>
