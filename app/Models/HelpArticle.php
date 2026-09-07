<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Une page du manuel d'utilisation integre (point 09). Contenu global,
 * identique pour tous les ministeres : pas de ministry_id ici, a la
 * difference de la quasi-totalite des autres modeles de l'application.
 */
class HelpArticle extends Model
{
    use HasUuid;

    protected $fillable = [
        'slug', 'module', 'title', 'body', 'order',
    ];
}
