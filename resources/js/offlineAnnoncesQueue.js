import { createOfflineQueue } from '@/offlineQueueFactory'

/**
 * Hors connexion : publication d'une annonce, sans piece jointe (2026-11-01).
 * Un fichier ne peut pas etre garde de facon fiable sur l'appareil : la page
 * de creation (Annonces/Create.vue) refuse l'enregistrement hors connexion
 * tant qu'une piece jointe est choisie, et le dit clairement.
 */
const file = createOfflineQueue({
    storageKey: 'oikonema-annonces-en-attente',
    endpoint: (orgUnitId) => `/org-units/${orgUnitId}/annonces`,
})

export const queueAnnonce = file.mettreEnFile
export const countQueuedAnnonces = file.compter
export const countRefusedAnnonces = file.compterRefuses
export const clearRefusedAnnonces = file.viderRefuses
export const syncQueuedAnnonces = file.envoyer
