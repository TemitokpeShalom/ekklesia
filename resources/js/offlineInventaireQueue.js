import { createOfflineQueue } from '@/offlineQueueFactory'

/**
 * Hors connexion : enregistrement d'un bien de l'inventaire (2026-11-01).
 * Voir offlineQueueFactory.js pour le principe general.
 */
const file = createOfflineQueue({
    storageKey: 'oikonema-inventaire-en-attente',
    endpoint: (orgUnitId) => `/org-units/${orgUnitId}/inventaire`,
})

export const queueBien = file.mettreEnFile
export const countQueuedBiens = file.compter
export const countRefusedBiens = file.compterRefuses
export const clearRefusedBiens = file.viderRefuses
export const syncQueuedBiens = file.envoyer
