<?php

namespace App\Services;

use App\Models\AttachmentCode;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Le mecanisme de rattachement par code (point 03) : un niveau superieur
 * autorise la creation d'un noeud sans jamais laisser choisir un parent
 * librement. Utilise depuis un controleur HTTP aujourd'hui, et pourra
 * demain etre appele a l'identique depuis une API mobile (point 10) - la
 * logique ne change pas, seule la couche de presentation change.
 */
class AttachmentCodeService
{
    /**
     * Emet un code a usage unique pour un niveau superieur donne.
     * Seul un compte disposant d'une affectation active habilitee sur ce
     * noeud peut en emettre un (verifie par la policy du controleur, pas
     * ici : cette classe ne connait pas la notion de "requete HTTP").
     */
    public function issue(OrgUnit $issuingOrgUnit, int $targetLevelRank, User $issuedBy, int $validForHours = 72): array
    {
        if ($targetLevelRank <= $issuingOrgUnit->level_rank) {
            throw new RuntimeException(
                "Un code emis par le rang {$issuingOrgUnit->level_rank} ne peut créer qu'un rang inférieur "
                .'(un district ne peut pas créer un autre district ou une région).'
            );
        }

        $plainCode = Str::upper(Str::random(4).'-'.Str::random(4));

        $attachmentCode = AttachmentCode::create([
            'ministry_id' => $issuingOrgUnit->ministry_id,
            'issuing_org_unit_id' => $issuingOrgUnit->id,
            'target_level_rank' => $targetLevelRank,
            'code_hash' => Hash::make($plainCode),
            'status' => 'pending',
            'issued_by' => $issuedBy->id,
            'expires_at' => now()->addHours($validForHours),
        ]);

        // Le code en clair n'est jamais stocke : il n'existe qu'ici, le
        // temps de le transmettre a la personne qui va l'utiliser.
        return [$attachmentCode, $plainCode];
    }

    /**
     * Consomme un code pour creer le nouveau noeud. parent_id, path et
     * ministry_id sont TOUJOURS herites du noeud emetteur - jamais saisis
     * par la personne qui remplit le formulaire (point 03).
     */
    public function consume(string $plainCode, array $newUnitAttributes, User $usedBy): OrgUnit
    {
        $candidates = AttachmentCode::where('status', 'pending')
            ->where('expires_at', '>', now())
            ->get();

        $attachmentCode = $candidates->first(
            fn (AttachmentCode $candidate) => Hash::check($plainCode, $candidate->code_hash)
        );

        if (! $attachmentCode) {
            throw new RuntimeException('Code de rattachement invalide, déjà utilisé ou expiré.');
        }

        return DB::transaction(function () use ($attachmentCode, $newUnitAttributes, $usedBy) {
            $issuingUnit = $attachmentCode->issuingOrgUnit;

            $newUnit = OrgUnit::create([
                'ministry_id' => $issuingUnit->ministry_id, // herite, jamais choisi
                'parent_id' => $issuingUnit->id,             // herite, jamais choisi
                'level_rank' => $attachmentCode->target_level_rank,
                'level_label' => $newUnitAttributes['level_label'],
                'name' => $newUnitAttributes['name'],
                'code' => $newUnitAttributes['code'],
                'metadata' => $newUnitAttributes['metadata'] ?? [],
                'status' => 'active',
                'path' => $issuingUnit->path.'.'.$newUnitAttributes['code'],
            ]);

            $newUnit->history()->create([
                'ministry_id' => $newUnit->ministry_id,
                'valid_from' => now()->toDateString(),
                'valid_to' => null,
                'name' => $newUnit->name,
                'level_rank' => $newUnit->level_rank,
                'level_label' => $newUnit->level_label,
                'parent_id' => $newUnit->parent_id,
                'path' => $newUnit->path,
                'transformation_type' => 'creation',
                'requested_by' => $usedBy->id,
                'approved_by' => $attachmentCode->issued_by,
                'reason' => 'Création via code de rattachement.',
            ]);

            $attachmentCode->update([
                'status' => 'utilise',
                'used_by' => $usedBy->id,
                'used_at' => now(),
                'created_org_unit_id' => $newUnit->id,
            ]);

            return $newUnit;
        });
    }
}
