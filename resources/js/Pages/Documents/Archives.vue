<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

/**
 * Archives de documents propres a l'entite (chantier "Documents",
 * 2026-09-12) - demande explicite du ministere : "chaque eglise locale ou
 * institution, selon son niveau, puisse enregistrer ses documents propres
 * comme archives". Statuts, actes, courriers... tout type de document
 * (voir ALLOWED_MIMES cote controleur), stocke sur le disque privé de
 * l'application (jamais public comme les photos/le logo : un document
 * d'archive peut etre sensible) et servi uniquement via une route
 * authentifiee.
 */
const props = defineProps({
    orgUnit: Object,
    canManage: Boolean,
    documents: Array,
})

const form = useForm({
    title: '',
    file: null,
})

const fileInput = ref(null)

function onFileChange(event) {
    form.file = event.target.files[0] ?? null
}

function submit() {
    form.post(`/org-units/${props.orgUnit.id}/documents/archives`, {
        forceFormData: true,
        onSuccess: () => {
            form.reset()
            if (fileInput.value) fileInput.value.value = ''
        },
    })
}

function destroy(doc) {
    if (!confirm(`Supprimer définitivement « ${doc.title} » ?`)) return
    router.delete(`/org-units/${props.orgUnit.id}/documents/archives/${doc.id}`)
}

function formatSize(kb) {
    const bytes = kb
    if (bytes < 1024) return `${bytes} o`
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} Ko`
    return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`
}

function dateLabel(value) {
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
    <AppLayout :org-unit="orgUnit" :back-href="`/org-units/${orgUnit.id}/documents`" back-label="Retour aux documents">
        <template #title>
            <div class="animate-[fadeInUp_0.5s_ease-out_both]">
                <p class="text-xs uppercase tracking-widest text-sanctuary/80 font-semibold flex items-center gap-2">
                    <span class="inline-block w-6 h-px bg-gold-soft/60"></span>
                    Documents · Archives
                </p>
                <h1 class="font-serif text-3xl text-graphite mt-2">Archives de {{ orgUnit.name }}</h1>
                <p class="text-sm text-graphite/70 mt-2 max-w-2xl">Statuts, actes, courriers, tout document propre à cette entité. Stockage privé : seules les personnes ayant accès à ce niveau peuvent les consulter.</p>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-8">
            <form v-if="canManage" @submit.prevent="submit" class="glass-panel rounded-3xl p-6 space-y-4 animate-[fadeInUp_0.55s_ease-out_both]">
                <h3 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest">Ajouter un document</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-graphite/87">Titre</label>
                        <input v-model="form.title" type="text" placeholder="Ex : Statuts de l'association"
                            class="w-full bg-graphite/5 border border-graphite/15 text-graphite placeholder-graphite/45 rounded-xl px-3.5 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold/60" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-rose-600">{{ form.errors.title }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-graphite/87">Fichier</label>
                        <input ref="fileInput" type="file" @change="onFileChange" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg,.webp"
                            class="w-full text-sm text-graphite/80 file:mr-3 file:rounded-lg file:border-0 file:bg-graphite/10 file:px-3 file:py-2 file:text-xs file:font-semibold hover:file:bg-graphite/15" />
                        <p v-if="form.errors.file" class="mt-1 text-xs text-rose-600">{{ form.errors.file }}</p>
                        <p class="mt-1 text-xs text-graphite/50">20 Mo max. PDF, Word, Excel, PowerPoint ou image.</p>
                    </div>
                </div>
                <button type="submit" :disabled="form.processing"
                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-gold to-gold-dark hover:shadow-glow-gold transition-all duration-300 text-night rounded-xl px-5 py-2.5 text-sm font-semibold shadow-lg shadow-gold/20 disabled:opacity-60">
                    Enregistrer dans les archives
                </button>
            </form>

            <section class="space-y-3 animate-[fadeInUp_0.6s_ease-out_both]">
                <h3 class="text-xs font-semibold text-sanctuary/80 uppercase tracking-widest px-1">{{ documents.length }} document(s) archivé(s)</h3>

                <div v-if="documents.length === 0" class="glass-panel rounded-2xl px-6 py-10 text-center text-sm text-graphite/60">
                    Aucun document archivé pour cette entité pour l'instant.
                </div>

                <div v-for="doc in documents" :key="doc.id" class="glass-panel rounded-2xl px-5 py-4 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-medium text-graphite truncate">{{ doc.title }}</p>
                        <p class="text-xs text-graphite/55 truncate">{{ doc.original_name }} · {{ formatSize(doc.size) }} · déposé le {{ dateLabel(doc.created_at) }}<span v-if="doc.uploader_name"> par {{ doc.uploader_name }}</span></p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a :href="`/org-units/${orgUnit.id}/documents/archives/${doc.id}/telecharger`"
                            class="text-xs font-semibold bg-graphite/5 hover:bg-graphite/10 text-graphite/80 rounded-full px-4 py-2 transition-colors">
                            Télécharger
                        </a>
                        <button v-if="canManage" type="button" @click="destroy(doc)"
                            class="text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-full px-4 py-2 transition-colors">
                            Supprimer
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
