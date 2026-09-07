<script setup>
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
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white px-6 py-4 flex items-center justify-between print:hidden">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Documentation</p>
                <h1 class="text-lg font-semibold">Manuel d'utilisation</h1>
            </div>
            <div class="flex items-center gap-4">
                <button type="button" @click="window.print()" class="text-sm text-slate-500 hover:text-slate-900">
                    Imprimer / exporter en PDF
                </button>
                <button type="button" @click="goBack" class="text-sm text-slate-500 hover:text-slate-900">
                    Retour
                </button>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-6 py-8 print:max-w-none print:px-0">
            <p class="text-sm text-slate-500 mb-8 print:hidden">
                Ce manuel grandit avec l'application : chaque écran livré reçoit sa page d'aide au moment où il est
                construit, à partir du code réellement écrit. Le même contenu est consultable ici en entier, et
                depuis le lien "Aide" de chaque écran concerné.
            </p>

            <nav class="mb-10 print:hidden">
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Sommaire</h2>
                <ul class="grid sm:grid-cols-2 gap-x-6 gap-y-1">
                    <li v-for="(items, moduleName) in modules" :key="moduleName">
                        <a :href="`#${moduleSlug(moduleName)}`" class="text-sm text-slate-700 hover:text-slate-900">{{ moduleName }}</a>
                    </li>
                </ul>
            </nav>

            <section
                v-for="(items, moduleName) in modules"
                :key="moduleName"
                :id="moduleSlug(moduleName)"
                class="mb-10 break-inside-avoid-page"
            >
                <h2 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-2 mb-4">{{ moduleName }}</h2>
                <article v-for="item in items" :key="item.slug" class="mb-8 break-inside-avoid-page">
                    <h3 class="text-sm font-semibold text-slate-800 mb-2">{{ item.title }}</h3>
                    <p v-for="(paragraph, i) in item.body.split('\n\n')" :key="i" class="text-sm text-slate-600 mb-2">
                        {{ paragraph }}
                    </p>
                </article>
            </section>
        </main>
    </div>
</template>
