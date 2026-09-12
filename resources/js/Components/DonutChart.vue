<script setup>
import { computed } from 'vue'

/**
 * Chantier "rapport financier" (2026-09-12, retour du ministere) : "des
 * graphes circulaires pour montrer que les entrées ont occupé telles
 * portions et les sorties ont occupé telles portions... on peut même
 * revenir encore pour détailler aussi pour les entrées". Petit graphe en
 * anneau, sans dépendance externe (meme principe que LineChart.vue) -
 * chaque segment est un arc de cercle trace via stroke-dasharray sur un
 * cercle, technique standard qui evite de calculer des chemins d'arc SVG
 * a la main.
 *
 * props.segments : [{ label, value, color }]
 */
const props = defineProps({
    segments: { type: Array, required: true },
    size: { type: Number, default: 160 },
})

const radius = computed(() => props.size / 2 - 14)
const circumference = computed(() => 2 * Math.PI * radius.value)
const total = computed(() => props.segments.reduce((sum, s) => sum + (s.value || 0), 0))

const arcs = computed(() => {
    let offset = 0
    return props.segments
        .filter((s) => (s.value || 0) > 0)
        .map((s) => {
            const fraction = total.value > 0 ? s.value / total.value : 0
            const length = fraction * circumference.value
            const arc = {
                ...s,
                fraction,
                dasharray: `${length} ${circumference.value - length}`,
                dashoffset: -offset,
            }
            offset += length
            return arc
        })
})
</script>

<template>
    <div class="flex items-center gap-5 flex-wrap">
        <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
            <g :transform="`translate(${size / 2}, ${size / 2}) rotate(-90)`">
                <circle v-if="total === 0" :r="radius" fill="none" stroke="currentColor" class="text-graphite/10" :stroke-width="14" />
                <circle
                    v-for="(arc, i) in arcs" :key="i"
                    :r="radius" fill="none" :stroke="arc.color" :stroke-width="14"
                    :stroke-dasharray="arc.dasharray" :stroke-dashoffset="arc.dashoffset"
                    stroke-linecap="butt"
                />
            </g>
            <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" class="fill-graphite" font-size="13" font-weight="600">
                {{ total > 0 ? '100%' : '—' }}
            </text>
        </svg>

        <ul class="space-y-1.5 text-xs">
            <li v-for="(arc, i) in arcs" :key="i" class="flex items-center gap-2 text-graphite/75">
                <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: arc.color }"></span>
                <span class="min-w-0 truncate">{{ arc.label }}</span>
                <span class="font-semibold text-graphite flex-shrink-0">{{ Math.round(arc.fraction * 100) }}%</span>
            </li>
            <li v-if="total === 0" class="text-graphite/50">Aucune donnée.</li>
        </ul>
    </div>
</template>
