<script setup>
import { Link } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'

/**
 * Calendrier annuel - refait le 2026-09-12 (retour du ministere : "je
 * voulais un calendrier normal avec les trente, trente-et-un jours du mois
 * comme tout autre calendrier... avec un intitulé du ministère... et aussi
 * peut-être renseigner le pasteur sur les événements de chaque jour, si
 * c'est son anniversaire, le pasteur peut facilement lui générer un
 * message pour le saluer"). Vraie grille de jours (voir
 * DocumentGeneratorController::calendrier), pas une simple liste triee
 * comme l'ancienne version.
 *
 * "Générer un message" : pas d'envoi automatique (aucune messagerie
 * SMS/WhatsApp n'est integree a la plateforme) - un brouillon de voeux est
 * propose, modifiable, puis ouvert directement dans WhatsApp (lien
 * wa.me pre-rempli) ou copié pour un autre canal. Rien n'est enregistré
 * côté serveur : c'est un outil du moment, pas un historique de messages.
 */
const props = defineProps({
    orgUnit: Object,
    ministry: Object,
    year: Number,
    months: Array,
    joursSemaine: Array,
})

function print() {
    window.print()
}

const openId = ref(null)
const drafts = reactive({})
const copiedId = ref(null)

function defaultMessage(member) {
    return `Joyeux anniversaire ${member.first_name} ! Toute la famille ${props.ministry.name} se réjouit avec vous en ce jour et vous souhaite une nouvelle année de grâce et de bénédictions. 🙏`
}

function toggle(member) {
    if (openId.value === member.id) {
        openId.value = null
        return
    }
    if (!(member.id in drafts)) {
        drafts[member.id] = defaultMessage(member)
    }
    openId.value = member.id
}

function whatsappHref(member) {
    const digits = (member.phone || '').replace(/[^+\d]/g, '').replace(/^\+/, '')
    const text = drafts[member.id] ?? defaultMessage(member)
    return `https://wa.me/${digits}?text=${encodeURIComponent(text)}`
}

async function copyMessage(member) {
    const text = drafts[member.id] ?? defaultMessage(member)
    try {
        await navigator.clipboard.writeText(text)
        copiedId.value = member.id
        setTimeout(() => { if (copiedId.value === member.id) copiedId.value = null }, 2000)
    } catch (e) {
        // Presse-papier indisponible selon le navigateur : rien de grave,
        // le texte reste visible et copiable a la main dans le champ.
    }
}
</script>

<template>
    <div class="min-h-screen bg-night print:bg-white">
        <header class="border-b border-white/10 glass-panel px-6 py-5 flex items-center justify-between print:hidden">
            <div>
                <p class="text-xs uppercase tracking-widest text-gold-soft/80 font-semibold">{{ orgUnit.level_label }}</p>
                <h1 class="font-serif text-xl text-white">{{ orgUnit.name }}</h1>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <Link :href="`/org-units/${orgUnit.id}/documents`" class="text-white/60 hover:text-white transition">Retour aux documents</Link>
                <button @click="print"
                    class="rounded-full bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night px-5 py-2 text-sm font-semibold shadow-md shadow-gold/20">
                    Imprimer
                </button>
            </nav>
        </header>

        <main class="max-w-4xl mx-auto px-6 py-10 print:max-w-none print:px-0 print:py-0 space-y-6 print:space-y-0">
            <p class="text-sm text-white/50 print:hidden">Calendrier annuel · {{ orgUnit.name }} · {{ year }}</p>

            <section v-for="(m, i) in months" :key="m.number"
                class="bg-white border border-coffee/10 rounded-3xl print:rounded-none print:border-0 p-8 print:min-h-screen print:flex print:flex-col"
                :class="{ 'print:break-after-page': i < months.length - 1 }">
                <div class="flex items-center justify-between border-b border-gold-soft pb-4 mb-5">
                    <div class="flex items-center gap-3">
                        <img v-if="ministry.logo_url" :src="ministry.logo_url" alt="" class="h-10 w-10 rounded-lg object-contain bg-white p-1 shadow" />
                        <div>
                            <p class="text-[11px] uppercase tracking-widest text-coffee-light">{{ ministry.name }}</p>
                            <h2 class="font-serif text-2xl text-ink">{{ m.label }} {{ year }}</h2>
                        </div>
                    </div>
                    <span class="text-sm text-coffee-light text-right">{{ orgUnit.name }}<br /><span class="text-xs">{{ orgUnit.level_label }}</span></span>
                </div>

                <!-- En-tetes des jours de la semaine, alignes sur la grille ci-dessous. -->
                <div class="grid grid-cols-7 gap-1 mb-1 text-center text-[10.5px] font-semibold uppercase tracking-wide text-coffee-light">
                    <span v-for="j in joursSemaine" :key="j">{{ j }}</span>
                </div>

                <!-- Grille du mois : cases vides de decalage, puis un jour par case (30/31 jours, comme n'importe quel calendrier). -->
                <div class="grid grid-cols-7 gap-1 flex-1">
                    <div v-for="n in m.leadingBlanks" :key="`blank-${n}`" aria-hidden="true"></div>
                    <div v-for="d in m.days" :key="d.day"
                        class="rounded-lg border border-coffee/10 p-1.5 min-h-[5.5rem] print:min-h-[3.4rem] print:break-inside-avoid flex flex-col"
                        :class="d.birthdays.length ? 'bg-parchment/60' : ''">
                        <span class="text-xs font-semibold text-ink/75">{{ d.day }}</span>

                        <div v-if="d.birthdays.length" class="mt-1 space-y-1">
                            <div v-for="b in d.birthdays" :key="b.id" class="text-[10px] leading-tight">
                                <button type="button" @click="toggle(b)"
                                    class="text-left text-gold-dark font-medium hover:underline print:pointer-events-none">
                                    🎂 {{ b.name }}
                                </button>

                                <!-- Panneau de generation de message, jamais imprime : outil du moment, pas un contenu du document papier. -->
                                <div v-if="openId === b.id" class="print:hidden mt-1.5 w-56 rounded-xl border border-gold-soft bg-white p-2.5 shadow-lg space-y-1.5 relative z-10">
                                    <textarea v-model="drafts[b.id]" rows="4" class="w-full text-[11px] leading-snug rounded-lg border border-coffee/20 p-1.5 focus:outline-none focus:ring-1 focus:ring-gold/60"></textarea>
                                    <div class="flex items-center gap-1.5">
                                        <a v-if="b.phone" :href="whatsappHref(b)" target="_blank" rel="noopener"
                                            class="inline-flex items-center gap-1 text-[10px] font-semibold bg-forest text-white rounded-lg px-2 py-1 hover:bg-forest/90">
                                            WhatsApp
                                        </a>
                                        <span v-else class="text-[9.5px] text-graphite/50">Aucun téléphone</span>
                                        <button type="button" @click="copyMessage(b)"
                                            class="text-[10px] font-medium bg-graphite/10 hover:bg-graphite/15 rounded-lg px-2 py-1">
                                            {{ copiedId === b.id ? 'Copié !' : 'Copier' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
