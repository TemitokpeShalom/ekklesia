<?php

namespace App\Services;

use App\Models\OrgUnit;
use Illuminate\Support\Str;

/**
 * Point 18 (Finances multi-normes) : separe la nature universelle d'un
 * mouvement financier (portee par FinancialTransaction::type/nature, voir
 * FinanceTransactionsController) de son rattachement a un plan de comptes,
 * qui depend du pays de l'entite qui l'enregistre, jamais de l'inverse.
 * Un pays sans norme documentee (voir config/finance.php) reste
 * pleinement utilisable : seule la couche universelle s'applique, sans
 * compte code, jusqu'a ce que sa norme soit ajoutee a la demande - jamais
 * par anticipation, jamais bloquant pour l'usage courant en attendant.
 */
class AccountingStandardResolver
{
    /**
     * Le noeud "Pays" (rang 2) dont depend cet org_unit : lui-meme s'il
     * est deja ce rang, sinon le premier ancetre de ce rang sur son
     * chemin materialise (meme requete ltree que OrgUnitPolicy/point 06).
     * Retourne null pour un Ministere ou un Continent pas encore rattache
     * a un pays.
     */
    public static function countryUnitFor(OrgUnit $orgUnit): ?OrgUnit
    {
        if ($orgUnit->level_rank === OrgUnit::RANK_PAYS) {
            return $orgUnit;
        }

        if ($orgUnit->level_rank < OrgUnit::RANK_PAYS || ! $orgUnit->path) {
            return null;
        }

        return OrgUnit::where('ministry_id', $orgUnit->ministry_id)
            ->where('level_rank', OrgUnit::RANK_PAYS)
            ->whereRaw('path @> ?::ltree', [$orgUnit->path])
            ->first();
    }

    /**
     * Le code de norme documentee pour cet org_unit : un choix explicite
     * (voir overrideFor()) prevaut toujours sur la detection automatique
     * par pays - c'est ce choix explicite que sert le bouton "Norme
     * comptable" du module Finances (retour du ministere, 2026-09-12 :
     * "il faut... choisir la norme comptable auquel la zone... obeit").
     */
    public static function codeFor(OrgUnit $orgUnit): ?string
    {
        if ($override = self::overrideFor($orgUnit)) {
            return $override;
        }

        $country = self::countryUnitFor($orgUnit);
        if (! $country) {
            return null;
        }

        return config('finance.country_standards.'.self::normalizeCountryName($country->name));
    }

    /**
     * Norme explicitement choisie (accounting_standard_override) sur ce
     * noeud ou le plus proche de ses ancetres qui en porte une - meme
     * regle "le plus specifique gagne" que le reste de l'architecture
     * (jamais deux valeurs contradictoires appliquees a la fois). Null si
     * aucun noeud de la chaine n'a de choix explicite : codeFor() retombe
     * alors sur la detection automatique par pays.
     */
    public static function overrideFor(OrgUnit $orgUnit): ?string
    {
        if (! $orgUnit->path) {
            return $orgUnit->accounting_standard_override;
        }

        return OrgUnit::where('ministry_id', $orgUnit->ministry_id)
            ->whereNotNull('accounting_standard_override')
            ->where(fn ($q) => $q->whereRaw('path @> ?::ltree', [$orgUnit->path])->orWhere('id', $orgUnit->id))
            ->orderByRaw('nlevel(path) DESC')
            ->value('accounting_standard_override');
    }

    /**
     * La definition complete de la norme (libelle + plan de comptes) pour
     * cet org_unit, ou null si non documentee - c'est cette valeur qui
     * decide, cote controleur, si le champ "compte comptable" est
     * proposé/exige ou si seule la couche universelle s'applique.
     */
    public static function forOrgUnit(OrgUnit $orgUnit): ?array
    {
        $code = self::codeFor($orgUnit);
        if (! $code) {
            return null;
        }

        $standard = config('finance.standards.'.$code);

        return $standard ? $standard + ['code' => $code] : null;
    }

    /**
     * La devise par defaut du pays dont depend cet org_unit, ou la devise
     * globale par defaut si ce pays n'a pas encore de devise documentee.
     */
    public static function currencyFor(OrgUnit $orgUnit): string
    {
        $country = self::countryUnitFor($orgUnit);
        if ($country) {
            $currency = config('finance.country_currencies.'.self::normalizeCountryName($country->name));
            if ($currency) {
                return $currency;
            }
        }

        return config('finance.default_currency');
    }

    /**
     * Minuscules, sans accents ni ponctuation - absorbe les variantes
     * d'ecriture usuelles d'un meme nom de pays (« Côte d'Ivoire »,
     * « Cote d Ivoire »...) sans imposer de liste ISO que rien ailleurs
     * dans l'application n'oblige encore a saisir.
     */
    public static function normalizeCountryName(string $name): string
    {
        $lower = mb_strtolower(Str::ascii($name));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $lower));
    }
}
