<script setup>
import { computed } from 'vue'

/**
 * En-tête officiel du ministère (2026-09-11) : demande explicite du
 * ministère - nom, sigle, n° d'autorisation, adresse du siège,
 * coordonnées et logo (saisis via Settings/MinistryInfo) doivent
 * apparaître, bien centrés, comme un en-tête de courrier officiel - "en
 * haut de l'espace de travail" (Dashboard) et "sur tout rapport financier,
 * rapport d'activités" (documents générés). Reçoit exactement la forme
 * renvoyée par Ministry::letterhead() cote serveur.
 *
 * Toutes les valeurs sont facultatives : un ministère qui n'a encore rien
 * renseigné (ex. le compte de démonstration) affiche seulement son nom -
 * jamais un bloc vide ou une mise en page cassée. Dès que ces informations
 * sont renseignées (Informations du ministère, dans le menu Gouvernance),
 * elles apparaissent ici automatiquement, sans rien à activer.
 */
const props = defineProps({
    ministry: { type: Object, required: true },
})

const contactLine = computed(() => (
    [props.ministry.phone, props.ministry.email, props.ministry.website].filter(Boolean).join(' · ')
))
</script>

<template>
    <div class="text-center pb-6 mb-8 border-b border-graphite/10 animate-[fadeInUp_0.45s_ease-out_both]">
        <img
            v-if="ministry.logo_url"
            :src="ministry.logo_url"
            :alt="ministry.name"
            class="mx-auto mb-3 h-16 w-16 rounded-xl bg-white object-contain p-1 shadow-glow-gold"
        />
        <p class="font-serif text-xl text-graphite">
            {{ ministry.name }}
            <span v-if="ministry.acronym" class="ml-1 font-sans text-base text-graphite/60">({{ ministry.acronym }})</span>
        </p>
        <p v-if="ministry.registration_number" class="mt-1 text-xs text-graphite/60">N° d'autorisation : {{ ministry.registration_number }}</p>
        <p v-if="ministry.headquarters_address" class="text-xs text-graphite/60">{{ ministry.headquarters_address }}</p>
        <p v-if="contactLine" class="mt-1 text-xs text-graphite/60">{{ contactLine }}</p>
    </div>
</template>
