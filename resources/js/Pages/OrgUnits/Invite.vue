<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * v3 "Vitrail" (2026-09-09) : migration de ce module vers la coquille
 * partagee AppLayout, sans aucun changement fonctionnel.
 */
const props = defineProps({
    orgUnit: Object,
    roles: Array,
})

const page = usePage()
const invitationLink = computed(() => page.props.flash?.invitation_link)

const form = useForm({
    role_id: '',
    email: '',
})

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/inviter`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
        },
    })
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}`">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Accès et postes
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">Inviter un titulaire</h1>
            </div>
        </template>

        <div class="max-w-lg mx-auto space-y-6">
            <div v-if="invitationLink" class="glass-panel rounded-2xl p-5 border-forest/40 animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="mb-2 text-sm font-medium text-forest">Invitation créée. Partagez ce lien à la personne concernée (WhatsApp, SMS...) :</p>
                <p class="break-all rounded-xl border border-graphite/15 bg-graphite/5 px-3.5 py-2.5 text-sm text-graphite/87">{{ invitationLink }}</p>
                <p class="mt-2 text-xs text-graphite/62">Ce lien est valable 7 jours et ne peut être utilisé qu'une seule fois.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4 glass-panel rounded-3xl p-6 md:p-7 animate-[fadeInUp_0.55s_ease-out_both]">
                <div>
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Poste à pourvoir</label>
                    <select v-model="form.role_id" required class="w-full bg-graphite/5 border border-graphite/15 text-graphite rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60">
                        <option value="" disabled class="bg-white text-graphite">Choisir un poste</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id" class="bg-white text-graphite">{{ role.label }}</option>
                    </select>
                    <p v-if="form.errors.role_id" class="mt-1 text-sm text-rose-600">{{ form.errors.role_id }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-graphite/87">Email (optionnel)</label>
                    <input v-model="form.email" type="email" placeholder="pour référence, le lien reste à partager manuellement" class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/55 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60"
                    >
                        Générer le lien d'invitation
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
