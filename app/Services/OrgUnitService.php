<?php

namespace App\Services;

use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Création DIRECTE d'une entité enfant, sans code de rattachement (retour
 * du ministère, 2026-09-12 : "le module de rattachement ne fonctionne pas
 * du tout... on va supprimer cette manière de faire"). Le responsable
 * descend jusqu'au nœud où il veut créer la nouvelle entité (via le
 * tableau de bord de ce nœud), reste sur cette page, et crée directement :
 * parent_id/ministry_id/path sont hérités du nœud courant EXACTEMENT
 * comme avec l'ancien mécanisme par code (point 03, voir
 * AttachmentCodeService::consume), jamais choisis librement - la seule
 * chose qui change est qu'aucun code n'est plus nécessaire pour ce geste.
 *
 * Le code de rattachement (AttachmentCodeService) reste dans le code pour
 * ne rien casser (une éventuelle donnée déjà émise), mais n'est plus
 * accessible depuis le menu Gouvernance (voir Dashboard/Index.vue) : la
 * création d'une entité passe désormais uniquement par ce service.
 *
 * Une fois la nouvelle entité créée, inviter son titulaire (Pasteur) se
 * fait via le mécanisme d'invitation déjà existant (InvitationService,
 * point 11) - désormais le SEUL usage restant d'un lien/code à transmettre
 * à quelqu'un pour rattacher une personne à une entité.
 */
class OrgUnitService
{
    // Libellés standard par rang (mêmes noms que l'ancien
    // IssueAttachmentCode.vue) : le rang reste la seule donnée fixe
    // (point 01), ce tableau ne fait qu'associer un nom lisible à chaque
    // rang au moment de la création - level_label reste ensuite librement
    // modifiable entité par entité (transformation, point 13).
    public const LEVEL_NAMES = [
        OrgUnit::RANK_MINISTERE => 'Ministère',
        OrgUnit::RANK_CONTINENT => 'Continent',
        OrgUnit::RANK_PAYS => 'Pays',
        OrgUnit::RANK_REGION => 'Région',
        OrgUnit::RANK_DISTRICT => 'District',
        OrgUnit::RANK_EGLISE_LOCALE => 'Église locale',
        OrgUnit::RANK_CELLULE => 'Cellule',
    ];

    public function createChild(OrgUnit $parent, string $name, int $levelRank, User $creator): OrgUnit
    {
        // Controle de quota d'abonnement (point 15/28/29, retour du
        // ministere 2026-09-12 : "il faut vraiment s'assurer que ca marche"
        // puis "ce n'est pas seulement eglise locale qu'il faut regarder...
        // tout est decompte en meme temps") - AUCUNE entite, quel que soit
        // son niveau, ne doit pouvoir se creer au-dela du plafond du palier
        // en cours, ici comme dans AttachmentCodeService : compter
        // uniquement les eglises locales aurait laisse une porte ouverte
        // (creer des districts a la place pour contourner le plafond).
        $parent->ministry->assertCanCreateOrgUnit();

        $code = $this->uniqueChildCode($parent, $name);

        $newUnit = OrgUnit::create([
            'ministry_id' => $parent->ministry_id, // hérité, jamais choisi
            'parent_id' => $parent->id,             // hérité : c'est le nœud sur lequel on se trouvait
            'level_rank' => $levelRank,
            'level_label' => self::LEVEL_NAMES[$levelRank] ?? 'Entité',
            'name' => $name,
            'code' => $code,
            'metadata' => [],
            'status' => 'active',
            'path' => $parent->path.'.'.$code,
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
            'requested_by' => $creator->id,
            'approved_by' => $creator->id,
            'reason' => 'Création directe depuis le tableau de bord (sans code de rattachement).',
        ]);

        return $newUnit;
    }

    /**
     * Même logique que l'ancien AttachmentCodeService::uniqueChildCode
     * (point 03) : le code (slug court) n'est unique que dans le périmètre
     * du parent (contrainte ['parent_id', 'code']) - la personne qui crée
     * ne connaît pas forcément les codes déjà pris sous ce nœud, on ajoute
     * un suffixe numérique plutôt que de faire échouer sur une contrainte
     * SQL avec un message illisible.
     */
    private function uniqueChildCode(OrgUnit $parent, string $desiredCode): string
    {
        $base = Str::slug($desiredCode) ?: 'entite';
        $code = $base;
        $suffix = 1;

        while (OrgUnit::where('parent_id', $parent->id)->where('code', $code)->exists()) {
            $suffix++;
            $code = "{$base}-{$suffix}";
        }

        return $code;
    }
}
