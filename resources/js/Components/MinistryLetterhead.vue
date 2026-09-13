<script setup>
import { computed } from 'vue'

/**
 * En-tête officiel du ministère (2026-09-11, mise en page revue le
 * 2026-09-13) : demande explicite du ministère - nom, sigle, n°
 * d'autorisation, adresse du siège, coordonnées et logo (saisis via
 * Settings/MinistryInfo) doivent apparaître comme un en-tête de courrier
 * officiel - "en haut de l'espace de travail" (Dashboard) et "sur tout
 * rapport financier, rapport d'activités" (documents générés). Reçoit
 * exactement la forme renvoyée par Ministry::letterhead() cote serveur.
 *
 * Corrige le 2026-09-13 (retour du ministere : "le logo doit garder sa
 * place à gauche... et les informations doivent être bien centrées... le
 * logo doit s'aligner avec le titre" - un peu comme sur tout document
 * officiel) : le logo, quand il y en a un, reste ancre a gauche et occupe
 * une largeur fixe (shrink-0) ; le nom/sigle/coordonnees restent centres,
 * mais dans l'espace restant a droite du logo (flex-1), pas sur la
 * largeur totale - et la ligne du logo s'aligne verticalement avec le
 * milieu de ce bloc de texte (items-center). Ce composant etant partage
 * (Dashboard ET tous les rapports/documents generes), corriger ici suffit
 * partout a la fois.
 *
 * Toutes les valeurs sont facultatives : un ministère qui n'a encore rien
 * renseigné (ex. le compte de démonstration) affiche seulement son nom -
 * jamais un bloc vide ou une mise en page cassée. Sans logo, le nom et les
 * coordonnées restent centrés sur la largeur totale, comme avant. Dès que
 * ces informations sont renseignées (Informations du ministère, dans le
 * menu Gouvernance), elles apparaissent ici automatiquement, sans rien à
 * activer.
 */
const props = defineProps({
    ministry: { type: Object, required: true },
})

const contactLine = computed(() => (
    [props.ministry.phone, props.ministry.email, props.ministry.website].filter(Boolean).join(' · ')
))
</script>

<template>
    <div class="flex items-center gap-4 pb-6 mb-8 border-b border-graphite/10 animate-[fadeInUp_0.45s_ease-out_both]">
        <img
            v-if="ministry.logo_url"
            :src="ministry.logo_url"
            :alt="ministry.name"
            class="h-16 w-16 rounded-xl bg-white object-contain p-1 shadow-glow-gold shrink-0"
        />
        <div class="flex-1 text-center">
            <p class="font-serif text-xl text-graphite">
                {{ ministry.name }}
                <span v-if="ministry.acronym" class="ml-1 font-sans text-base text-graphite/60">({{ ministry.acronym }})</span>
            </p>
            <p v-if="ministry.registration_number" class="mt-1 text-xs text-graphite/60">N° d'autorisation : {{ ministry.registration_number }}</p>
            <p v-if="ministry.headquarters_address" class="text-xs text-graphite/60">{{ ministry.headquarters_address }}</p>
            <p v-if="contactLine" class="mt-1 text-xs text-graphite/60">{{ contactLine }}</p>
        </div>
    </div>
</template>
