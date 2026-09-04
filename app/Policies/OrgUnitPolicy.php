<?php

namespace App\Policies;

use App\Models\OrgUnit;
use App\Models\User;

/**
 * Droits en cascade (point 05) : un titulaire de role peut agir sur son
 * propre noeud ou n'importe lequel de ses descendants, jamais au-dela -
 * la meme requete ltree que la consolidation (point 06) et les annonces
 * (point 07) sert ici a verifier "ce noeud est-il sous mon perimetre ?".
 */
class OrgUnitPolicy
{
    public function view(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasAffectationOverridingDescendantsOrSelf($user, $orgUnit);
    }

    public function issueAttachmentCode(User $user, OrgUnit $orgUnit): bool
    {
        return $user->activeAffectations()
            ->where('org_unit_id', $orgUnit->id)
            ->whereHas('role', fn ($q) => $q->where('can_manage_users', true))
            ->exists();
    }

    public function inviteTo(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasManagingAffectationOverDescendantsOrSelf($user, $orgUnit);
    }

    private function hasAffectationOverridingDescendantsOrSelf(User $user, OrgUnit $orgUnit): bool
    {
        return $user->activeAffectations()
            ->whereHas(
                'orgUnit',
                fn ($q) => $q->whereRaw('org_units.path @> ?::ltree OR org_units.id = ?', [$orgUnit->path, $orgUnit->id])
            )
            ->exists();
    }

    private function hasManagingAffectationOverDescendantsOrSelf(User $user, OrgUnit $orgUnit): bool
    {
        return $user->activeAffectations()
            ->whereHas('role', fn ($q) => $q->where('can_manage_users', true))
            ->whereHas(
                'orgUnit',
                fn ($q) => $q->whereRaw('org_units.path @> ?::ltree OR org_units.id = ?', [$orgUnit->path, $orgUnit->id])
            )
            ->exists();
    }
}
