import { createOfflineQueue } from '@/offlineQueueFactory'

/**
 * Hors connexion : envoi d'un signalement (2026-11-01). Le texte reste sur
 * l'appareil de la personne jusqu'a l'envoi, puis il est transmis comme un
 * signalement ordinaire (anonyme ou non, selon son choix).
 */
const file = createOfflineQueue({
    storageKey: 'oikonema-signalements-en-attente',
    endpoint: (orgUnitId) => `/org-units/${orgUnitId}/signalements`,
})

export const queueSignalement = file.mettreEnFile
export const countQueuedSignalements = file.compter
export const countRefusedSignalements = file.compterRefuses
export const clearRefusedSignalements = file.viderRefuses
export const syncQueuedSignalements = file.envoyer
