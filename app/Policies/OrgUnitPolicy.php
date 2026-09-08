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

    /**
     * Gestion des membres (fideles) : meme regle que l'invitation d'un
     * titulaire de role - il faut un role habilite a gerer des personnes
     * (can_manage_users), sur ce noeud ou un de ses ancetres.
     */
    public function manageMembers(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasManagingAffectationOverDescendantsOrSelf($user, $orgUnit);
    }

    /**
     * Gestion des cultes (services) : meme regle que la gestion des
     * membres - il faut un role habilite a gerer des personnes
     * (can_manage_users), sur ce noeud ou un de ses ancetres.
     */
    public function manageCultes(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasManagingAffectationOverDescendantsOrSelf($user, $orgUnit);
    }

    /**
     * Gestion des sacrements individuels (baptemes, mariages, point 08) :
     * meme regle que la gestion des membres et des cultes - il faut un role
     * habilite a gerer des personnes (can_manage_users), sur ce noeud ou
     * un de ses ancetres.
     */
    public function manageSacrements(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasManagingAffectationOverDescendantsOrSelf($user, $orgUnit);
    }

    /**
     * Gestion du parcours de disciple (etapes de croissance spirituelle,
     * point 08) : meme regle que la gestion des membres et des cultes - il
     * faut un role habilite a gerer des personnes (can_manage_users), sur
     * ce noeud ou un de ses ancetres.
     */
    public function manageDiscipleship(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasManagingAffectationOverDescendantsOrSelf($user, $orgUnit);
    }

    /**
     * Gestion des finances (mouvements et rapport d'activites) : meme
     * regle que la gestion des membres et des cultes - il faut un role
     * habilite a gerer des personnes (can_manage_users), sur ce noeud ou
     * un de ses ancetres.
     */
    public function manageFinances(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasManagingAffectationOverDescendantsOrSelf($user, $orgUnit);
    }
    /**
     * Publication des annonces (point 07) : meme regle que les autres
     * modules de gestion - il faut un role habilite a gerer des personnes
     * (can_manage_users), sur ce noeud ou un de ses ancetres. La
     * visibilite en lecture, elle, ne passe pas par cette policy : elle
     * suit la regle symetrique de point 06 (voir AnnouncementsController::index).
     */
    public function manageAnnouncements(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasManagingAffectationOverDescendantsOrSelf($user, $orgUnit);
    }

    /**
     * Traitement des signalements (point 17) : changer le statut d'une
     * preoccupation remontee necessite le meme role habilite a gerer des
     * personnes que les autres modules de gestion, sur ce noeud ou un de
     * ses ancetres. Le depot d'un signalement, lui, suit la regle plus
     * large de view() - n'importe quel titulaire voyant ce noeud peut y
     * signaler une preoccupation (voir SignalementsController::store).
     */
    public function manageSignalements(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasManagingAffectationOverDescendantsOrSelf($user, $orgUnit);
    }

    /**
     * Transformation organisationnelle (point 13 : renommage, promotion,
     * rattachement, fermeture) : meme regle que les autres modules de
     * gestion - il faut un role habilite a gerer des personnes
     * (can_manage_users), sur ce noeud ou un de ses ancetres. Scission et
     * fusion ne passent pas encore par cette policy, aucun ecran ne les
     * declenchant pour l'instant.
     */
    public function transform(User $user, OrgUnit $orgUnit): bool
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
