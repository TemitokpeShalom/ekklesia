<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un document d'archive propre a un OrgUnit (voir migration
 * 2026_09_12_000003_create_org_unit_documents_table) - jamais servi par une
 * URL publique, toujours par un telechargement authentifie (voir
 * OrgUnitDocumentsController::download).
 */
class OrgUnitDocument extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'uploaded_by',
        'title', 'file_path', 'original_name', 'mime', 'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
