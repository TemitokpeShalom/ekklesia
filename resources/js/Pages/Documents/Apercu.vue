<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    orgUnit: Object,
    ministry: Object,
    template: String,
    year: Number,
    leaders: { type: Array, default: () => [] },
    memberCount: { type: Number, default: 0 },
    months: { type: Array, default: () => [] },
})

const titles = { affiche: 'Affiche', calendrier: 'Calendrier annuel' }

function print() {
    window.print()
}
</script>

<template>
    <div class="min-h-screen bg-night print:bg-white">
        <!--
            v3 "Vitrail" (2026-09-09) : seule cette barre de navigation (masquee
            a l'impression) reprend l'habillage sombre commun. Le contenu
            imprimable ci-dessous reste volontairement en parchemin clair : ce
            n'est pas un ecran d'application mais un gabarit destine au papier.
        -->
        <header class="border-b border-white/10 glass-panel px-6 py-5 flex items-center justify-between print:hidden">
            <div>
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold">{{ orgUnit.level_label }}</p>
                <h1 class="font-serif text-xl text-white">{{ orgUnit.name }}</h1>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <Link :href="`/org-units/${orgUnit.id}/documents`" class="text-white/60 hover:text-white transition">Retour au générateur</Link>
                <button @click="print"
                    class="rounded-full bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night px-5 py-2 text-sm font-semibold shadow-md shadow-gold/20">
                    Imprimer
                </button>
            </nav>
        </header>

        <main class="max-w-3xl mx-auto px-6 py-10 print:max-w-none print:px-0 print:py-0">
            <p class="text-sm text-white/50 mb-6 print:hidden">{{ titles[template] }} · {{ orgUnit.name }}</p>

            <!-- Affiche : une page unique, identite de l'entite. -->
            <section v-if="template === 'affiche'"
                class="relative overflow-hidden rounded-3xl print:rounded-none bg-gradient-to-br from-sanctuary-dark via-sanctuary to-coffee-dark text-white px-10 py-14 print:min-h-screen flex flex-col items-center text-center">
                <div class="absolute -top-24 -right-16 w-96 h-96 bg-gold/25 rounded-full blur-3xl" aria-hidden="true"></div>
                <div class="absolute -bottom-32 -left-16 w-96 h-96 bg-slateblue/20 rounded-full blur-3xl" aria-hidden="true"></div>

                <svg class="absolute inset-0 m-auto w-[30rem] h-[30rem] opacity-[0.07] pointer-events-none"
                    viewBox="0 0 200 200" fill="none" stroke="white" stroke-width="1.2" aria-hidden="true">
                    <path d="M20 150 C60 135,90 135,100 145 C110 135,140 135,180 150 L180 160 C140 145,110 145,100 155 C90 145,60 145,20 160 Z" />
                    <path d="M100 145 L100 155" />
                    <path d="M30 145 C55 133,80 133,95 141" />
                    <path d="M30 152 C55 140,80 140,95 148" />
                    <path d="M170 145 C145 133,120 133,105 141" />
                    <path d="M170 152 C145 140,120 140,105 148" />
                    <path d="M100 38 L100 92" />
                    <path d="M76 58 L124 58" />
                    <circle cx="100" cy="95" r="46" stroke-opacity="0.5" />
                </svg>

                <p class="relative text-xs uppercase tracking-[0.3em] text-gold-soft/90 font-semibold">{{ ministry.name }}</p>
                <h2 class="relative font-serif text-5xl mt-4 mb-2">{{ orgUnit.name }}</h2>
                <p class="relative text-white/80">{{ orgUnit.level_label }}</p>

                <div class="relative w-16 h-px bg-gold-soft/50 my-8"></div>

                <div v-if="leaders.length" class="relative mb-8">
                    <p class="text-xs uppercase tracking-widest text-gold-soft/90 font-semibold mb-3">Responsables</p>
                    <ul class="space-y-1">
                        <li v-for="l in leaders" :key="l.id" class="text-lg">
                            <span class="font-serif">{{ l.title }} {{ l.name }}</span>
                        </li>
                    </ul>
                </div>

                <p class="relative text-sm text-white/70">{{ memberCount }} membre(s) actif(s)</p>
            </section>

            <!-- Calendrier annuel : 12 pages, une par mois. -->
            <div v-if="template === 'calendrier'" class="space-y-6 print:space-y-0">
                <section v-for="(m, i) in months" :key="m.number"
                    class="bg-white border border-coffee/10 rounded-3xl print:rounded-none print:border-0 p-8 print:min-h-screen print:flex print:flex-col"
                    :class="{ 'print:break-after-page': i < months.length - 1 }">
                    <div class="flex items-baseline justify-between border-b border-gold-soft pb-4 mb-6">
                        <h2 class="font-serif text-3xl text-ink">{{ m.label }}</h2>
                        <span class="text-sm text-coffee-light">{{ orgUnit.name }} · {{ year }}</span>
                    </div>

                    <div v-if="m.members.length === 0" class="text-sm text-coffee-light italic print:hidden">
                        Aucun anniversaire enregistré ce mois-ci.
                    </div>
                    <ul v-else class="space-y-2">
                        <li v-for="mem in m.members" :key="mem.id"
                            class="flex items-center gap-4 rounded-xl px-4 py-2.5 odd:bg-parchment">
                            <span class="font-serif text-xl text-gold-dark w-8 text-right shrink-0">{{ mem.day }}</span>
                            <span class="text-ink">{{ mem.name }}</span>
                        </li>
                    </ul>
                </section>
            </div>
        </main>
    </div>
</template>
