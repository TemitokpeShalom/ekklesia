<?php

namespace App\Console\Commands;

use App\Models\AssistantPlatformFeedback;
use Illuminate\Console\Command;

/**
 * Seule facon de consulter assistant_platform_feedback (voir la migration
 * de creation de la table pour le raisonnement complet) : cette table est
 * volontairement SANS RLS et SANS route applicative, donc reservee a
 * Martin via la console serveur - jamais exposee a un utilisateur de la
 * plateforme, quel que soit son role.
 *
 * Usage : php artisan assistant:feedback [--status=nouveau] [--traiter=ID]
 */
class AssistantFeedbackListCommand extends Command
{
    protected $signature = 'assistant:feedback {--status=} {--traiter=}';

    protected $description = "Liste (ou marque traitee) les remontees de l'assistant IA vers l'equipe technique.";

    public function handle(): int
    {
        if ($id = $this->option('traiter')) {
            $feedback = AssistantPlatformFeedback::findOrFail($id);
            $feedback->update(['status' => 'traite']);
            $this->info("Signalement {$id} marque comme traite.");

            return self::SUCCESS;
        }

        $query = AssistantPlatformFeedback::with(['ministry:id,name'])
            ->orderByDesc('created_at');

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->info('Aucun signalement.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Date', 'Ministère', 'Statut', 'Message'],
            $rows->map(fn ($f) => [
                $f->id,
                $f->created_at->format('Y-m-d H:i'),
                $f->ministry?->name ?? '—',
                $f->status,
                \Illuminate\Support\Str::limit($f->message, 80),
            ])
        );

        return self::SUCCESS;
    }
}
