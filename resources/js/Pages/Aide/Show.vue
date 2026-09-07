<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    article: Object,
    modules: Object,
})

function goBack() {
    window.history.back()
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white px-6 py-4 flex items-center justify-between print:hidden">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ article.module }}</p>
                <h1 class="text-lg font-semibold">{{ article.title }}</h1>
            </div>
            <div class="flex items-center gap-4">
                <button type="button" @click="window.print()" class="text-sm text-slate-500 hover:text-slate-900">
                    Imprimer
                </button>
                <button type="button" @click="goBack" class="text-sm text-slate-500 hover:text-slate-900">
                    Retour
                </button>
            </div>
        </header>

        <div class="max-w-5xl mx-auto px-6 py-8 grid md:grid-cols-[240px_minmax(0,1fr)] gap-10">
            <nav class="print:hidden">
                <Link href="/aide" class="text-sm font-medium text-slate-900 hover:underline">Manuel complet</Link>
                <div v-for="(items, moduleName) in modules" :key="moduleName" class="mt-5">
                    <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">{{ moduleName }}</h2>
                    <ul class="space-y-1">
                        <li v-for="item in items" :key="item.slug">
                            <Link
                                :href="`/aide/${item.slug}`"
                                class="text-sm block"
                                :class="item.slug === article.slug ? 'text-slate-900 font-medium' : 'text-slate-500 hover:text-slate-900'"
                            >
                                {{ item.title }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>

            <article class="max-w-2xl">
                <p v-for="(paragraph, i) in article.body.split('\n\n')" :key="i" class="text-sm text-slate-700 mb-4">
                    {{ paragraph }}
                </p>
            </article>
        </div>
    </div>
</template>
