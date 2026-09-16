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
     * Création directe d'une entité enfant à ce nœud (retour du ministère,
     * 2026-09-12 : remplace le mécanisme par code, point 03) - même règle
     * que les autres modules de gestion : un rôle habilité à gérer des
     * personnes (can_manage_users), sur ce nœud OU un de ses ancêtres, pas
     * seulement exactement sur ce nœud (contrairement à l'ancienne
     * habilitation issueAttachmentCode, plus utilisée depuis le menu) - un
     * responsable descend depuis son propre niveau jusqu'au nœud voulu, il
     * n'a pas forcément une affectation exactement sur ce nœud précis.
     */
    public function createChild(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasManagingAffectationOverDescendantsOrSelf($user, $orgUnit);
    }

    /**
     * Gestion des membres (fideles) : corrige le 2026-09-14 (retour du
     * ministere : le secretaire general, charge en pratique des fiches de
     * membres et des cultes, n'y avait aucun acces) - decouple desormais de
     * can_manage_users (reserve aux comptes/a la structure) au profit de
     * can_manage_activities ("les activites de l'eglise"), sur ce noeud ou
     * un de ses ancetres.
     */
    public function manageMembers(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasAffectationWithFlagOverDescendantsOrSelf($user, $orgUnit, 'can_manage_activities');
    }

    /**
     * Gestion des cultes (services) : meme regle que la gestion des
     * membres (can_manage_activities, voir 2026-09-14 ci-dessus), sur ce
     * noeud ou un de ses ancetres.
     */
    public function manageCultes(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasAffectationWithFlagOverDescendantsOrSelf($user, $orgUnit, 'can_manage_activities');
    }

    /**
     * Gestion des sacrements individuels (baptemes, mariages, point 08) :
     * meme regle que la gestion des membres et des cultes
     * (can_manage_activities, voir 2026-09-14 ci-dessus), sur ce noeud ou
     * un de ses ancetres.
     */
    public function manageSacrements(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasAffectationWithFlagOverDescendantsOrSelf($user, $orgUnit, 'can_manage_activities');
    }

    /**
     * Gestion du parcours de disciple (etapes de croissance spirituelle,
     * point 08) : meme regle que la gestion des membres et des cultes
     * (can_manage_activities, voir 2026-09-14 ci-dessus), sur ce noeud ou
     * un de ses ancetres.
     */
    public function manageDiscipleship(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasAffectationWithFlagOverDescendantsOrSelf($user, $orgUnit, 'can_manage_activities');
    }

    /**
     * Gestion des equipes et du benevolat (point 08), y compris
     * l'affectation des membres a une equipe : meme regle que la gestion
     * des membres et des cultes (can_manage_activities, voir 2026-09-14
     * ci-dessus), sur ce noeud ou un de ses ancetres.
     */
    public function manageTeams(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasAffectationWithFlagOverDescendantsOrSelf($user, $orgUnit, 'can_manage_activities');
    }

    /**
     * Gestion des finances (mouvements, rapport, inventaire des biens,
     * norme comptable) : corrige le 2026-09-14 (retour du ministere,
     * demande explicite "que le tresorier puisse renseigner tout ce qui
     * est lie aux finances") - controlee desormais par can_manage_finances,
     * un indicateur distinct de can_manage_activities (le tresorier ne
     * gere pas les membres/cultes, et reciproquement), sur ce noeud ou un
     * de ses ancetres.
     */
    public function manageFinances(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasAffectationWithFlagOverDescendantsOrSelf($user, $orgUnit, 'can_manage_finances');
    }
    /**
     * Publication des annonces (point 07) : meme regle que les autres
     * modules de gestion (can_manage_activities, voir 2026-09-14
     * ci-dessus), sur ce noeud ou un de ses ancetres. La visibilite en
     * lecture, elle, ne passe pas par cette policy : elle suit la regle
     * symetrique de point 06 (voir AnnouncementsController::index).
     */
    public function manageAnnouncements(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasAffectationWithFlagOverDescendantsOrSelf($user, $orgUnit, 'can_manage_activities');
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

    /**
     * Archives de documents propres a une entite (chantier "module
     * Documents", 2026-09-12) : meme regle que les autres modules de
     * gestion (can_manage_activities, voir 2026-09-14 ci-dessus), sur ce
     * noeud ou un de ses ancetres. La lecture (liste, telechargement) suit
     * view(), plus permissive.
     */
    public function manageDocuments(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasAffectationWithFlagOverDescendantsOrSelf($user, $orgUnit, 'can_manage_activities');
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

    /**
     * Operations structurelles/de compte (inviter, creer une entite
     * enfant, transformer la structure, traiter un signalement) : restent
     * reservees a can_manage_users (Pasteur + administrateur technique),
     * inchange depuis le 2026-09-14 - volontairement distinct de
     * can_manage_activities/can_manage_finances ci-dessous, plus sensible.
     */
    private function hasManagingAffectationOverDescendantsOrSelf(User $user, OrgUnit $orgUnit): bool
    {
        return $this->hasAffectationWithFlagOverDescendantsOrSelf($user, $orgUnit, 'can_manage_users');
    }

    /**
     * Corrige le 2026-09-14 : generalisation de l'ancienne
     * hasManagingAffectationOverDescendantsOrSelf (qui ne testait QUE
     * can_manage_users) pour accepter n'importe quel indicateur booleen de
     * la table roles - permet de separer can_manage_activities ("les
     * activites de l'eglise", demande pour le secretaire) de
     * can_manage_finances (demande pour le tresorier/comptable) sans
     * dupliquer cette requete trois fois.
     */
    private function hasAffectationWithFlagOverDescendantsOrSelf(User $user, OrgUnit $orgUnit, string $roleFlag): bool
    {
        return $user->activeAffectations()
            ->whereHas('role', fn ($q) => $q->where($roleFlag, true))
            ->whereHas(
                'orgUnit',
                fn ($q) => $q->whereRaw('org_units.path @> ?::ltree OR org_units.id = ?', [$orgUnit->path, $orgUnit->id])
            )
            ->exists();
    }
}
