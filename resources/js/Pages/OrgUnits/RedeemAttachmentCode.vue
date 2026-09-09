<script setup>
import { useForm } from '@inertiajs/vue3'

/**
 * Page manquante jusqu'ici (audit du 2026-09-09, point 3) : le code de
 * rattachement (voir IssueAttachmentCode.vue) n'avait nulle part où être
 * saisi - seule la route POST existait, sans écran. Accessible sans
 * compte (le code lui-même est la preuve de mandat, point 03) : une
 * personne responsable d'une église réellement nouvelle n'a encore aucun
 * compte Ekklesia, donc le formulaire crée les deux à la fois - la
 * nouvelle entité ET son compte - dans le même geste, comme pour une
 * invitation (point 11). Même habillage autonome que Auth/Login.vue et
 * Invitations/Accept.vue : cette page doit s'afficher correctement pour
 * quelqu'un qui n'a pas encore de session.
 */
const props = defineProps({
    authenticated: Boolean,
    prefillCode: { type: String, default: null },
})

const form = useForm({
    code: props.prefillCode ?? '',
    name: '',
    level_label: '',
    code_short: '',
    account_name: '',
    account_email: '',
    account_phone: '',
    account_password: '',
    account_password_confirmation: '',
})

function submit() {
    form.post('/rattachement')
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center px-4 py-12 relative overflow-hidden bg-night">
        <div class="absolute -top-24 -right-16 w-[28rem] h-[28rem] bg-sanctuary/26 rounded-full blur-[100px]" style="animation: driftGlow 14s ease-in-out infinite;" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-16 w-[28rem] h-[28rem] bg-gold/18 rounded-full blur-[100px]" style="animation: driftGlow 18s ease-in-out infinite reverse;" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-vitrail" aria-hidden="true"></div>

        <form @submit.prevent="submit"
            class="relative w-full max-w-md glass-panel p-8 rounded-3xl shadow-2xl shadow-black/40 border-t-2 border-t-gold/70 animate-[fadeInUp_0.6s_ease-out_both]">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-gradient-to-br from-gold to-gold-dark text-night font-serif font-bold shadow-glow-gold mb-5">E</span>
            <p class="text-xs uppercase tracking-widest text-gold-soft/90 font-semibold mb-1">Rattachement</p>
            <h1 class="font-serif text-2xl text-white mb-1">Saisir un code de rattachement</h1>
            <p class="text-sm text-white/60 mb-7">
                Le code vous a été transmis par le responsable qui l'a émis. Il crée une seule nouvelle entité,
                rattachée automatiquement sous la sienne.
            </p>

            <p v-if="form.errors.code" class="text-sm text-rose-400 mb-4 rounded-xl bg-rose-400/10 border border-rose-400/30 px-3.5 py-2.5">
                {{ form.errors.code }}
            </p>

            <label class="block text-sm font-medium text-white/80 mb-1">Code de rattachement</label>
            <input v-model="form.code" required placeholder="XXXX-XXXX"
                class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-4 tracking-widest uppercase transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />

            <div class="border-t border-white/10 pt-4 mb-4">
                <p class="text-xs uppercase tracking-widest text-white/40 font-semibold mb-3">La nouvelle entité</p>

                <label class="block text-sm font-medium text-white/80 mb-1">Nom (ex. « Église APC Cotonou »)</label>
                <input v-model="form.name" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                <p v-if="form.errors.name" class="text-sm text-rose-400 mb-3 -mt-2">{{ form.errors.name }}</p>

                <label class="block text-sm font-medium text-white/80 mb-1">Niveau (ex. « Église locale », « District »)</label>
                <input v-model="form.level_label" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                <p v-if="form.errors.level_label" class="text-sm text-rose-400 mb-3 -mt-2">{{ form.errors.level_label }}</p>

                <label class="block text-sm font-medium text-white/80 mb-1">Code court (optionnel)</label>
                <input v-model="form.code_short" placeholder="Généré automatiquement à partir du nom si laissé vide"
                    class="w-full bg-white/5 border border-white/15 text-white placeholder-white/30 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
            </div>

            <template v-if="!authenticated">
                <div class="border-t border-white/10 pt-4 mb-2">
                    <p class="text-xs uppercase tracking-widest text-white/40 font-semibold mb-1">Votre compte</p>
                    <p class="text-xs text-white/45 mb-3">
                        Vous devenez le titulaire (Pasteur) de cette nouvelle entité. Ce compte est personnel : il
                        vous appartient, même si votre poste change plus tard.
                    </p>

                    <label class="block text-sm font-medium text-white/80 mb-1">Nom complet</label>
                    <input v-model="form.account_name" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.account_name" class="text-sm text-rose-400 mb-3 -mt-2">{{ form.errors.account_name }}</p>

                    <label class="block text-sm font-medium text-white/80 mb-1">Adresse e-mail</label>
                    <input v-model="form.account_email" type="email" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.account_email" class="text-sm text-rose-400 mb-3 -mt-2">{{ form.errors.account_email }}</p>

                    <label class="block text-sm font-medium text-white/80 mb-1">Téléphone (optionnel)</label>
                    <input v-model="form.account_phone" class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />

                    <label class="block text-sm font-medium text-white/80 mb-1">Mot de passe</label>
                    <input v-model="form.account_password" type="password" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-3 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.account_password" class="text-sm text-rose-400 mb-3 -mt-2">{{ form.errors.account_password }}</p>

                    <label class="block text-sm font-medium text-white/80 mb-1">Confirmer le mot de passe</label>
                    <input v-model="form.account_password_confirmation" type="password" required class="w-full bg-white/5 border border-white/15 text-white rounded-xl px-3.5 py-2.5 mb-1 transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                </div>
            </template>
            <p v-else class="text-xs text-white/45 mb-2 border-t border-white/10 pt-4">
                Vous êtes déjà connecté : la nouvelle entité sera rattachée à votre compte.
            </p>

            <button type="submit" :disabled="form.processing"
                class="w-full mt-4 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl py-3 font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
                Rattacher cette entité
            </button>
        </form>
    </div>
</template>
