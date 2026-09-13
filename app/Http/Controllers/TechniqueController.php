<?php

namespace App\Http\Controllers;

use App\Models\Ministry;
use App\Models\TechnicalStaff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Espace technique Oikonema (2026-09-13) : reserve a l'equipe technique
 * (voir EnsureTechnicalStaff), transversal a tous les ministeres - jamais
 * le tableau de bord d'UN ministere precis (voir DashboardController pour
 * celui-la). Premiere livraison : vue d'ensemble des abonnements de tous
 * les ministeres, et gestion de l'equipe elle-meme. Le canal de signalement
 * des ministeres vers l'equipe technique (distinct du signalement interne
 * existant, voir SignalementsController) arrivera dans une prochaine
 * livraison.
 */
class TechniqueController extends Controller
{
    public function index(): Response
    {
        // org_units est protegee par la RLS multi-tenant (voir la migration
        // d'activation) : invisible tant que app.current_ministry_id ne
        // correspond pas exactement (fail closed, point 04). Cet ecran est
        // justement le seul endroit ou il faut lire, tour a tour, le compte
        // de CHAQUE ministere - on bascule donc le contexte sur chacun le
        // temps de son seul comptage, jamais plus largement, et on le remet
        // a vide une fois termine.
        $ministries = Ministry::query()
            ->with('plan:id,name')
            ->orderBy('name')
            ->get()
            ->map(function (Ministry $ministry) {
                DB::statement('SELECT set_config(?, ?, false)', ['app.current_ministry_id', $ministry->id]);

                return [
                    'id' => $ministry->id,
                    'name' => $ministry->name,
                    'short_code' => $ministry->short_code,
                    'plan_name' => $ministry->plan?->name,
                    'subscription_status' => $ministry->subscription_status,
                    'on_trial' => $ministry->onTrial(),
                    'trial_ends_at' => optional($ministry->trial_ends_at)->toIso8601String(),
                    'current_period_ends_at' => optional($ministry->current_period_ends_at)->toIso8601String(),
                    'org_units_count' => $ministry->orgUnitsCount(),
                    'created_at' => $ministry->created_at->toIso8601String(),
                ];
            });

        DB::statement('SELECT set_config(?, ?, false)', ['app.current_ministry_id', '']);

        return Inertia::render('Technique/Dashboard', [
            'ministries' => $ministries,
            'summary' => [
                'total' => $ministries->count(),
                'en_essai' => $ministries->where('subscription_status', 'essai')->count(),
                'actifs' => $ministries->where('subscription_status', 'active')->count(),
                'expires' => $ministries->where('subscription_status', 'expiree')->count(),
            ],
        ]);
    }

    public function equipe(): Response
    {
        return Inertia::render('Technique/Equipe', [
            'membres' => TechnicalStaff::with(['user:id,name,email', 'addedBy:id,name'])
                ->latest()
                ->get()
                ->map(fn (TechnicalStaff $membre) => [
                    'id' => $membre->id,
                    'name' => $membre->user->name,
                    'email' => $membre->user->email,
                    'added_by' => $membre->addedBy?->name,
                    'created_at' => $membre->created_at->toIso8601String(),
                ]),
            // Comptes donnant acces via TECHNICAL_STAFF_EMAILS (.env, voir
            // config/oikonema.php) et pas encore repris dans la table : montres
            // ici pour que l'equipe sache qui a acces sans regarder le
            // serveur - jamais retirables depuis cet ecran (il faudrait
            // modifier le .env lui-meme), affichage seul.
            'amorcesParEmail' => collect(config('oikonema.technical_staff_emails', []))
                ->reject(fn ($email) => TechnicalStaff::whereHas('user', fn ($q) => $q->where('email', $email))->exists())
                ->values(),
        ]);
    }

    public function storeStaff(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return back()->with('error', "Aucun compte Oikonema n'existe avec l'adresse {$data['email']}. La personne doit d'abord avoir un compte (dans n'importe quel ministère) avant de rejoindre l'équipe technique.");
        }

        if ($user->isTechnicalStaff()) {
            return back()->with('error', 'Cette personne fait déjà partie de l\'équipe technique.');
        }

        TechnicalStaff::create([
            'user_id' => $user->id,
            'added_by' => $request->user()->id,
        ]);

        return redirect()->route('technique.equipe')->with('success', 'Ajouté à l\'équipe technique.');
    }

    public function destroyStaff(TechnicalStaff $technicalStaff): RedirectResponse
    {
        $technicalStaff->delete();

        return redirect()->route('technique.equipe')->with('success', 'Retiré de l\'équipe technique.');
    }
}
