<script setup>
import { computed } from 'vue'

/**
 * Chantier "rapport d'activités" (2026-09-12, retour du ministere) :
 * "une présentation graphique de l'évolution des effectifs sous forme
 * d'une courbe... pour montrer comment les effectifs évoluent chaque
 * dimanche". Petit graphe en courbes, multi-séries, sans dépendance
 * externe (SVG écrit à la main, comme le reste de la plateforme évite
 * toute nouvelle dépendance non indispensable) - s'imprime aussi
 * correctement qu'un texte, contrairement à un graphe rendu en <canvas>.
 *
 * props.series : [{ name, color, values: number[] }]
 * props.labels : string[] (même longueur que chaque `values`)
 */
const props = defineProps({
    labels: { type: Array, required: true },
    series: { type: Array, required: true },
    height: { type: Number, default: 200 },
})

const width = 640
const padding = { top: 16, right: 16, bottom: 28, left: 32 }

const maxValue = computed(() => {
    const all = props.series.flatMap((s) => s.values)
    const max = Math.max(1, ...all.map((v) => v ?? 0))
    // Un peu de marge au-dessus du point le plus haut, jamais collé au bord.
    return Math.ceil(max * 1.15)
})

const innerWidth = computed(() => width - padding.left - padding.right)
const innerHeight = computed(() => props.height - padding.top - padding.bottom)

const stepX = computed(() => (props.labels.length > 1 ? innerWidth.value / (props.labels.length - 1) : 0))

function xFor(i) {
    return padding.left + i * stepX.value
}

function yFor(value) {
    const ratio = (value ?? 0) / maxValue.value
    return padding.top + innerHeight.value * (1 - ratio)
}

const gridLines = computed(() => {
    const count = 4
    return Array.from({ length: count + 1 }, (_, i) => {
        const ratio = i / count
        return {
            y: padding.top + innerHeight.value * (1 - ratio),
            value: Math.round(maxValue.value * ratio),
        }
    })
})

const linePaths = computed(() => props.series.map((s) => ({
    ...s,
    d: s.values.map((v, i) => `${i === 0 ? 'M' : 'L'} ${xFor(i)} ${yFor(v)}`).join(' '),
    points: s.values.map((v, i) => ({ x: xFor(i), y: yFor(v), value: v })),
})))
</script>

<template>
    <div>
        <svg :viewBox="`0 0 ${width} ${height}`" class="w-full" :style="{ height: height + 'px' }" preserveAspectRatio="none">
            <!-- Grille horizontale legere -->
            <g v-for="(g, i) in gridLines" :key="i">
                <line :x1="padding.left" :x2="width - padding.right" :y1="g.y" :y2="g.y" stroke="currentColor" class="text-graphite/10" stroke-width="1" />
                <text :x="padding.left - 6" :y="g.y + 3" text-anchor="end" class="fill-graphite/45" font-size="9">{{ g.value }}</text>
            </g>

            <!-- Une courbe par serie -->
            <g v-for="line in linePaths" :key="line.name">
                <path :d="line.d" fill="none" :stroke="line.color" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <circle v-for="(p, i) in line.points" :key="i" :cx="p.x" :cy="p.y" r="2.6" :fill="line.color" />
            </g>

            <!-- Repere de dates en abscisse -->
            <text v-for="(label, i) in labels" :key="label" :x="xFor(i)" :y="height - 8" text-anchor="middle" class="fill-graphite/55" font-size="9">{{ label }}</text>
        </svg>

        <div class="flex flex-wrap gap-4 mt-1 justify-center">
            <span v-for="s in series" :key="s.name" class="inline-flex items-center gap-1.5 text-xs text-graphite/70">
                <span class="inline-block w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: s.color }"></span>
                {{ s.name }}
            </span>
        </div>
    </div>
</template>
