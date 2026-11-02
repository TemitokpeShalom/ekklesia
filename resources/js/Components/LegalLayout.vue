<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
    titre: String,
    version: String,
})
</script>

<template>
    <Head :title="titre" />
    <div class="min-h-screen bg-[#f6f7f9] px-4 py-8 sm:py-12">
        <div class="mx-auto w-full max-w-3xl">
            <a href="/bienvenue" class="inline-flex items-center gap-2 text-sm text-sanctuary hover:underline mb-5">
                <img src="/images/oikonema-icon.png" alt="" class="w-7 h-7 rounded-lg" />
                Retour à Oikonema
            </a>
            <article class="bg-white rounded-2xl shadow-sm border border-graphite/10 p-6 sm:p-10 legal-texte">
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-graphite mb-1">{{ titre }}</h1>
                <p class="text-xs text-graphite/55 mb-6">Version du {{ version }}</p>
                <slot />
            </article>
            <p class="text-center text-xs text-graphite/50 mt-5">
                <a href="/conditions-utilisation" class="hover:underline">Conditions d'utilisation</a>
                <span class="mx-1.5">·</span>
                <a href="/confidentialite" class="hover:underline">Confidentialité</a>
            </p>
        </div>
    </div>
</template>

<style>
.legal-texte h2 { font-family: inherit; font-size: 1.05rem; font-weight: 700; margin: 1.6rem 0 .5rem; text-transform: none; letter-spacing: 0; color: #1f2937; }
.legal-texte p, .legal-texte li { font-size: .95rem; line-height: 1.65; color: rgba(31,41,55,.85); }
.legal-texte p { margin: .5rem 0; }
.legal-texte ul { list-style: disc; padding-left: 1.3rem; margin: .5rem 0; }
.legal-texte li { margin: .25rem 0; }
</style>
