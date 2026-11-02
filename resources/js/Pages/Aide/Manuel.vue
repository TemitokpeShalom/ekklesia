<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import manuel from '@/manuel'

/**
 * Manuel illustre : meme contenu que le document Word/PDF
 * (public/manuel/Manuel-Oikonema.*). Les images sont des captures de
 * l'application annotees (cadres et numeros rouges).
 */
const groups = []
for (const s of manuel.sections) {
    let g = groups.find((x) => x.name === s.group)
    if (!g) groups.push((g = { name: s.group, items: [] }))
    g.items.push(s)
}
const zoom = ref(null)
</script>

<template>
    <AppLayout>
        <template #actions>
            <a href="/manuel/Manuel-Oikonema.pdf" download class="text-sm font-semibold text-white bg-gradient-to-r from-azure to-azure-dark rounded-full px-4 py-2 print:hidden">
                Télécharger en PDF
            </a>
            <a href="/aide" class="text-sm text-graphite/80 hover:text-graphite bg-graphite/5 border border-graphite/10 rounded-full px-4 py-2 print:hidden">
                Aide
            </a>
        </template>
        <template #title>
            <div class="print:hidden">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold">{{ manuel.subtitle }}</p>
                <h1 class="font-serif text-4xl sm:text-5xl font-bold text-graphite mt-2">{{ manuel.title }}</h1>
            </div>
        </template>

        <div class="max-w-5xl mx-auto grid lg:grid-cols-[220px_minmax(0,1fr)] gap-8">
            <nav class="hidden lg:block sticky top-4 self-start text-sm print:hidden">
                <a href="#vision" class="block font-medium text-graphite mb-3">L'organisation</a>
                <div v-for="g in groups" :key="g.name" class="mb-3">
                    <p class="text-xs uppercase tracking-widest text-graphite/55 mb-1">{{ g.name }}</p>
                    <a v-for="s in g.items" :key="s.id" :href="`#${s.id}`" class="block text-graphite/70 hover:text-graphite py-0.5">{{ s.title }}</a>
                </div>
            </nav>

            <div>
                <section class="glass-panel rounded-3xl p-6 mb-6">
                    <p v-for="(t, i) in manuel.intro" :key="i" class="text-sm text-graphite/80 mb-3 last:mb-0">{{ t }}</p>
                </section>

                <section id="vision" class="glass-panel rounded-3xl p-6 mb-8">
                    <h2 class="font-serif text-2xl font-bold text-graphite mb-3">{{ manuel.visionTitle }}</h2>
                    <p v-for="(t, i) in manuel.vision" :key="i" class="text-sm text-graphite/80 mb-3">{{ t }}</p>
                    <button type="button" class="block w-full" @click="zoom = `/manuel/${manuel.visionImg}`">
                        <img :src="`/manuel/${manuel.visionImg}`" alt="Schéma de l'organisation" class="w-full rounded-2xl border border-graphite/10" loading="lazy" />
                    </button>
                    <p class="text-xs text-graphite/60 italic mt-2">{{ manuel.visionCaption }}</p>
                </section>

                <template v-for="g in groups" :key="g.name">
                    <h2 class="font-serif text-3xl font-bold text-graphite mt-10 mb-4">{{ g.name }}</h2>
                    <section v-for="s in g.items" :id="s.id" :key="s.id" class="glass-panel rounded-3xl p-5 sm:p-6 mb-6 scroll-mt-4 break-inside-avoid-page">
                        <h3 class="font-serif text-2xl font-bold text-graphite mb-1">{{ s.title }}</h3>
                        <p class="text-sm text-graphite/75 mb-4">{{ s.intro }}</p>
                        <div class="flex flex-col sm:flex-row gap-5 items-start">
                            <button v-if="s.img" type="button" class="shrink-0 mx-auto sm:mx-0 w-full max-w-[300px]" @click="zoom = `/manuel/${s.img}`">
                                <img :src="`/manuel/${s.img}`" :alt="s.title" class="w-full rounded-2xl border border-graphite/10 shadow-sm" loading="lazy" />
                                <span class="block text-xs text-graphite/55 mt-1 print:hidden">Appuyez pour agrandir</span>
                            </button>
                            <ol v-if="s.type === 'num'" class="flex-1 space-y-3">
                                <li v-for="(l, i) in s.legend" :key="i" class="flex gap-3 text-sm text-graphite/85">
                                    <span class="shrink-0 w-6 h-6 rounded-full bg-rose-600 text-white text-xs font-bold flex items-center justify-center mt-0.5">{{ i + 1 }}</span>
                                    <span>{{ l }}</span>
                                </li>
                            </ol>
                            <ul v-else class="flex-1 space-y-2 list-disc pl-5 text-sm text-graphite/85">
                                <li v-for="(l, i) in s.legend" :key="i">{{ l }}</li>
                            </ul>
                        </div>
                        <p v-if="s.note" class="mt-4 text-sm text-graphite/80 bg-azure/5 border border-azure/15 rounded-2xl px-4 py-3">
                            <strong class="text-azure-dark">À retenir : </strong>{{ s.note }}
                        </p>
                    </section>
                </template>

                <section class="glass-panel rounded-3xl p-6 mt-10">
                    <h2 class="font-serif text-2xl font-bold text-graphite mb-3">{{ manuel.techTitle }}</h2>
                    <p v-for="(t, i) in manuel.tech" :key="i" class="text-sm text-graphite/80 mb-3">{{ t }}</p>
                </section>
            </div>
        </div>

        <div v-if="zoom" class="fixed inset-0 z-50 bg-black/80 overflow-auto p-4 print:hidden" @click="zoom = null">
            <img :src="zoom" alt="" class="mx-auto max-w-full sm:max-w-3xl rounded-xl bg-white" />
        </div>
    </AppLayout>
</template>
