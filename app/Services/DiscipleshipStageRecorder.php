<?php

namespace App\Services;

use App\Models\DiscipleshipStage;
use App\Models\Member;

/**
 * Automatisation du Parcours de disciple (chantier 2026-09-12, retour du
 * ministere) : plutôt que d'obliger le pasteur à revenir manuellement dans
 * ce module cocher une étape déjà évidente ailleurs dans l'application, on
 * l'enregistre automatiquement au moment où l'événement qui la prouve est
 * lui-même enregistré. Utilisé actuellement par :
 *  - MembersController::store() -> "Nouveau converti" à l'enregistrement d'un membre ;
 *  - SacramentsController::store()/update() -> "Baptisé" quand un baptême est enregistré ;
 *  - TeamMembersController::store() -> l'étape choisie par le ministère pour chaque équipe (Team::grants_stage).
 *
 * Une seule ligne par (membre, étape) est maintenue à jour (updateOrCreate) :
 * "un seul baptême pour la personne" - si l'événement source est corrigé
 * (une date de baptême modifiée, par exemple), la date de l'étape suit
 * automatiquement, sans dupliquer l'entrée à chaque correction. Ceci ne
 * s'applique qu'aux étapes déclenchées automatiquement : une étape ajoutée
 * à la main depuis le Parcours de disciple reste un journal libre,
 * inchangé, où plusieurs lignes de la même étape restent possibles si le
 * pasteur le souhaite.
 */
class DiscipleshipStageRecorder
{
    public static function record(Member $member, string $stage, ?string $reachedAt = null): void
    {
        if (! array_key_exists($stage, DiscipleshipStage::STAGES)) {
            return;
        }

        DiscipleshipStage::updateOrCreate(
            ['member_id' => $member->id, 'stage' => $stage],
            [
                'ministry_id' => $member->ministry_id,
                'org_unit_id' => $member->org_unit_id,
                'reached_at' => $reachedAt ?: now()->toDateString(),
            ]
        );
    }
}
