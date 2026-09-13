<?php

namespace App\Http\Controllers;

use App\Models\AssistantPlatformFeedback;
use App\Models\Ministry;
use App\Models\Plan;
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
 * celui-la).
 *
 * Retour du ministere (13/09, apres la premiere livraison) : deux ajouts.
 * (1) updateSubscription permet de regler a la main le statut d'abonnement
 * d'un ministere ("honorer" ou "exonerer" quelqu'un sans passer par
 * FedaPay/crypto) - aucun nouveau statut invente : "exonerer" durablement,
 * c'est simplement statut actif + echeance laissee vide (Ministry::
 * subscriptionActive() traite deja une echeance nulle comme illimitee).
 * (2) signalements()/updateSignalementStatus() exposent enfin, dans cet
 * espace, les remontees "vers Martin" faites depuis le widget assistant
 * (AssistantPlatformFeedback) - jusqu'ici visibles UNIQUEMENT via la
 * commande serveur `php artisan assistant:feedback` (voir
 * AssistantFeedbackListCommand, laissee en place, mais plus le seul moyen).
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
                    'plan_id' => $ministry->plan_id,
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
            'plans' => Plan::orderBy('sort_order')->get(['id', 'name']),
            'summary' => [
                'total' => $ministries->count(),
                'en_essai' => $ministries->where('subscription_status', 'essai')->count(),
                'actifs' => $ministries->where('subscription_status', 'active')->count(),
                'expires' => $ministries->where('subscription_status', 'expiree')->count(),
                'nouveaux_signalements' => AssistantPlatformFeedback::where('status', 'nouveau')->count(),
            ],
        ]);
    }

    /**
     * Reglage manuel de l'abonnement d'un ministere (retour du 13/09 :
     * "il y aura des ministères qu'on va honorer et exonérer de
     * l'abonnement, par contre d'autres, je ne veux pas les honorer").
     * Aucune verification de paiement ici, a la difference de
     * SubscriptionFedapayController/SubscriptionCryptoController : c'est
     * precisement le point, une decision manuelle de l'equipe technique.
     */
    public function updateSubscription(Request $request, Ministry $ministry): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['nullable', 'uuid', 'exists:plans,id'],
            'subscription_status' => ['required', 'in:essai,active,expiree'],
            'trial_ends_at' => ['nullable', 'date'],
            'current_period_ends_at' => ['nullable', 'date'],
        ]);

        $ministry->update($data);

        return back()->with('success', "Abonnement de {$ministry->name} mis à jour.");
    }

    /**
     * Signalements techniques (retour du 13/09) : remontees faites depuis
     * le widget assistant (bouton "signaler un problème à l'équipe
     * technique" dans sa fenetre) - table SANS RLS des sa creation (voir la
     * migration), jusqu'ici uniquement lisible via la commande serveur
     * `php artisan assistant:feedback`. Cet ecran ne la remplace pas (elle
     * reste utilisable), il evite juste d'avoir a s'en servir.
     */
    public function signalements(): Response
    {
        return Inertia::render('Technique/Signalements', [
            'signalements' => AssistantPlatformFeedback::with(['ministry:id,name', 'user:id,name,email'])
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (AssistantPlatformFeedback $f) => [
                    'id' => $f->id,
                    'category' => $f->category,
                    'message' => $f->message,
                    'status' => $f->status,
                    'ministry_name' => $f->ministry?->name,
                    'user_name' => $f->user?->name,
                    'user_email' => $f->user?->email,
                    'created_at' => $f->created_at->toIso8601String(),
                ]),
        ]);
    }

    public function updateSignalementStatus(Request $request, AssistantPlatformFeedback $signalement): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:nouveau,lu,traite'],
        ]);

        $signalement->update($data);

        return back()->with('success', 'Statut mis à jour.');
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
            return back()->with('error', "Aucun compte Oikonema n'existe avec l'adresse {$data['email']}. Si cette adresse est déjà dans TECHNICAL_STAFF_EMAILS (.env), la personne peut créer elle-même son compte technique sur /equipe-technique/creer-mon-compte. Sinon, ajoute d'abord son adresse au .env du serveur.");
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
