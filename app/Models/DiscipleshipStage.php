<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscipleshipStage extends Model
{
    use HasUuid;

    /**
     * Etapes fixes du parcours de disciple (point 08). Volontairement non
     * configurables par ministere (contrairement aux titres honorifiques) :
     * ce sont des jalons universels de croissance, pas une convention
     * culturelle locale - c'est pourquoi un sacrement propre a une seule
     * confession (ex. la communion catholique) n'y figure pas : voir la
     * reponse donnee au ministere le 2026-09-12 au sujet du "mode" par
     * confession.
     *
     * Corrige le 2026-09-12 (retour du ministere, "est-ce que ça couvre
     * tout ?") : deux jalons manquaient par rapport au parcours
     * d'integration le plus repandu dans les eglises evangeliques/
     * pentecotistes francophones (dont celles a "cellules", deja un rang
     * d'OrgUnit dans cette plateforme) - "en_consolidation" (le suivi
     * rapproche d'un nouveau converti, en cellule ou en binome, avant le
     * bapteme) et "membre" (l'adhesion formelle, distincte du bapteme dans
     * beaucoup de ministeres). Ajout pur : ne renomme ni ne retire aucune
     * etape existante, donc aucune donnee deja enregistree n'est affectee.
     */
    public const STAGES = [
        'nouveau_converti' => 'Nouveau converti',
        'en_consolidation' => 'En consolidation',
        'baptise' => 'Baptisé',
        'membre' => 'Membre affilié',
        'en_formation' => 'En formation',
        'engage_service' => 'Engagé dans le service',
        'envoye_leader' => 'Envoyé / leader',
    ];

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'member_id', 'stage', 'reached_at', 'notes', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'reached_at' => 'date',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
