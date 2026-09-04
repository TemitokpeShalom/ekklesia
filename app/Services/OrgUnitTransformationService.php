<?php

namespace App\Services;

use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Transformations organisationnelles (point 13) : promotion, rattachement,
 * renommage. L'identite permanente (org_units.id) ne change JAMAIS ; seuls
 * les attributs historises changent, et chaque changement ferme la ligne
 * d'historique en cours pour en ouvrir une nouvelle (point 02).
 *
 * Fusion/scission/fermeture ne sont pas construites dans ce premier
 * module (point 10 - feuille de route) : seules promotion, rattachement
 * et renommage, les plus fréquents, le sont ; le schema (point 02) est
 * prêt à accueillir les autres sans être reconstruit.
 */
class OrgUnitTransformationService
{
    private const ALLOWED_PROMOTIONS = [
        OrgUnit::RANK_CELLULE => OrgUnit::RANK_EGLISE_LOCALE,
        OrgUnit::RANK_EGLISE_LOCALE => OrgUnit::RANK_DISTRICT,
        OrgUnit::RANK_DISTRICT => OrgUnit::RANK_REGION,
        OrgUnit::RANK_REGION => OrgUnit::RANK_PAYS,
    ];

    public function promote(OrgUnit $orgUnit, User $requestedBy, User $approvedBy, string $reason): OrgUnit
    {
        $targetRank = self::ALLOWED_PROMOTIONS[$orgUnit->level_rank] ?? null;

        if ($targetRank === null) {
            throw new RuntimeException("Aucune promotion directe n'est autorisée depuis ce rang.");
        }

        return $this->applyTransformation($orgUnit, $requestedBy, $approvedBy, 'promotion', $reason, [
            'level_rank' => $targetRank,
        ]);
    }

    public function reattach(OrgUnit $orgUnit, OrgUnit $newParent, User $requestedBy, User $approvedBy, string $reason): OrgUnit
    {
        if ($newParent->level_rank >= $orgUnit->level_rank) {
            throw new RuntimeException('Le nouveau parent doit être à un rang strictement supérieur.');
        }

        return $this->applyTransformation($orgUnit, $requestedBy, $approvedBy, 'rattachement', $reason, [
            'parent_id' => $newParent->id,
            'path' => $newParent->path.'.'.$orgUnit->code,
        ]);
    }

    public function rename(OrgUnit $orgUnit, string $newName, User $requestedBy, User $approvedBy, string $reason): OrgUnit
    {
        return $this->applyTransformation($orgUnit, $requestedBy, $approvedBy, 'renommage', $reason, [
            'name' => $newName,
        ]);
    }

    /**
     * Le coeur du mecanisme : ferme la ligne d'historique en vigueur,
     * met a jour org_units (etat courant), ouvre une nouvelle ligne
     * d'historique - en une seule transaction, jamais un etat intermediaire
     * visible (point 13).
     */
    private function applyTransformation(
        OrgUnit $orgUnit,
        User $requestedBy,
        User $approvedBy,
        string $type,
        string $reason,
        array $changes
    ): OrgUnit {
        return DB::transaction(function () use ($orgUnit, $requestedBy, $approvedBy, $type, $reason, $changes) {
            $effectiveDate = now()->toDateString();

            $orgUnit->history()
                ->whereNull('valid_to')
                ->update(['valid_to' => $effectiveDate]);

            $orgUnit->fill($changes);
            $orgUnit->save();

            $orgUnit->history()->create([
                'ministry_id' => $orgUnit->ministry_id,
                'valid_from' => $effectiveDate,
                'valid_to' => null,
                'name' => $orgUnit->name,
                'level_rank' => $orgUnit->level_rank,
                'level_label' => $orgUnit->level_label,
                'parent_id' => $orgUnit->parent_id,
                'path' => $orgUnit->path,
                'transformation_type' => $type,
                'requested_by' => $requestedBy->id,
                'approved_by' => $approvedBy->id,
                'reason' => $reason,
            ]);

            return $orgUnit->fresh();
        });
    }

    /**
     * Reconstitue l'etat d'un noeud tel qu'il existait a une date donnee -
     * utilise par les rapports consolides historiques (point 06/13),
     * jamais par la lecture de l'etat courant (qui lit org_units).
     */
    public function stateAsOf(OrgUnit $orgUnit, string $date): ?array
    {
        return $orgUnit->history()
            ->where('valid_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', $date))
            ->first()
            ?->only(['name', 'level_rank', 'level_label', 'parent_id', 'path']);
    }
}
