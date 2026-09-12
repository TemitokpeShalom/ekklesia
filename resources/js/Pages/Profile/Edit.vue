<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * "Mon profil" (v4 "Constellation", 2026-09-10) : demande explicite du
 * ministere lors de la refonte visuelle - voir ProfileController pour le
 * detail (pourquoi l'email n'est pas modifiable ici, pourquoi aucun
 * OrgUnit n'est requis). Accessible depuis le menu "profil" de l'en-tete
 * (AppLayout.vue), visible sur tout ecran de l'outil de travail.
 */
const props = defineProps({
    profileUser: Object,
    affectations: Array,
})

const infoForm = useForm({
    name: props.profileUser.name,
    phone: props.profileUser.phone,
})

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

function submitInfo() {
    infoForm.put('/profil')
}

function submitPassword() {
    passwordForm.put('/profil/mot-de-passe', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    })
}

// Corrige le 2026-09-12 (retour du ministere, valable pour TOUTES les
// pages) : cette page n'a aucun bouton "Retour" - meme mecanisme que
// Aide/Index.vue et Aide/Show.vue, deux ecrans hors contexte d'un OrgUnit
// precis comme celui-ci.
function goBack() {
    window.history.back()
}
</script>

<template>
    <AppLayout>
        <template #actions>
            <button type="button" @click="goBack" class="text-sm text-graphite/80 hover:text-graphite bg-graphite/5 hover:bg-graphite/10 border border-graphite/10 rounded-full px-4 py-2 transition-colors">
                Retour
            </button>
        </template>
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Mon profil
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">{{ profileUser.name }}</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto space-y-8">
            <section v-if="affectations.length" class="glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.5s_ease-out_both]">
                <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest mb-4">Mes affectations</h2>
                <div class="flex flex-wrap gap-2">
                    <span v-for="a in affectations" :key="a.id"
                        class="inline-flex items-center gap-1.5 glass-panel-light rounded-full pl-3 pr-4 py-1.5 text-sm">
                        <span class="font-semibold text-sanctuary">{{ a.role }}</span>
                        <span class="text-graphite/68">· {{ a.org_unit }}</span>
                    </span>
                </div>
            </section>

            <form @submit.prevent="submitInfo" class="glass-panel rounded-3xl p-6 md:p-7 space-y-5 animate-[fadeInUp_0.55s_ease-out_both]">
                <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest">Informations</h2>

                <div>
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Nom complet</label>
                    <input v-model="infoForm.name" type="text" required
                        class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="infoForm.errors.name" class="mt-1 text-sm text-rose-600">{{ infoForm.errors.name }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Email</label>
                    <input :value="profileUser.email" type="email" disabled
                        class="w-full bg-graphite/[0.03] border border-graphite/10 text-graphite/55 rounded-xl px-3.5 py-2.5 text-sm cursor-not-allowed" />
                    <p class="mt-1 text-xs text-graphite/55">Identifiant de connexion, non modifiable ici. Contactez l'équipe Oikonema pour en changer.</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Téléphone</label>
                    <input v-model="infoForm.phone" type="text"
                        class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="infoForm.errors.phone" class="mt-1 text-sm text-rose-600">{{ infoForm.errors.phone }}</p>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" :disabled="infoForm.processing"
                        class="bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
                        Enregistrer
                    </button>
                </div>
            </form>

            <form @submit.prevent="submitPassword" class="glass-panel rounded-3xl p-6 md:p-7 space-y-5 animate-[fadeInUp_0.6s_ease-out_both]">
                <h2 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest">Mot de passe</h2>

                <div>
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Mot de passe actuel</label>
                    <input v-model="passwordForm.current_password" type="password" required autocomplete="current-password"
                        class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-rose-600">{{ passwordForm.errors.current_password }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-graphite/87">Nouveau mot de passe</label>
                        <input v-model="passwordForm.password" type="password" required autocomplete="new-password"
                            class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-rose-600">{{ passwordForm.errors.password }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-graphite/87">Confirmer</label>
                        <input v-model="passwordForm.password_confirmation" type="password" required autocomplete="new-password"
                            class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" :disabled="passwordForm.processing"
                        class="bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
                        Modifier le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
