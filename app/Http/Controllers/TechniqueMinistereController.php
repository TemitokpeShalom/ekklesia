<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Member;
use App\Models\Ministry;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Fiche d'un ministere pour l'equipe technique : qui y a acces, combien de
 * membres, paiements recus, et de quoi depanner sans la console (nouveau mot
 * de passe pour une personne bloquee).
 */
class TechniqueMinistereController extends Controller
{
    public function show(Ministry $ministry): Response
    {
        // Memes precautions que TechniqueController::index : la RLS cache les
        // donnees d'un ministere tant que le contexte n'est pas fixe dessus.
        DB::statement('SELECT set_config(?, ?, false)', ['app.current_ministry_id', $ministry->id]);

        try {
            $affectations = Affectation::with(['user:id,name,email,phone', 'role', 'orgUnit:id,name'])
                ->where('ministry_id', $ministry->id)
                ->where('status', 'active')
                ->get();

            $membersCount = Member::count();
            $orgUnitsCount = $ministry->orgUnitsCount();
        } finally {
            DB::statement('SELECT set_config(?, ?, false)', ['app.current_ministry_id', '']);
        }

        $users = $affectations->groupBy('user_id')->map(function ($rows) {
            $user = $rows->first()->user;

            return [
                'id' => $user?->id,
                'name' => $user?->name,
                'email' => $user?->email,
                'phone' => $user?->phone,
                'roles' => $rows->map(fn ($a) => trim(($a->role?->label ?? $a->role?->name ?? 'Rôle').' · '.($a->orgUnit?->name ?? '')))->values(),
            ];
        })->filter(fn ($u) => $u['id'])->values();

        $payments = SubscriptionPayment::where('ministry_id', $ministry->id)
            ->orderByDesc('created_at')->limit(15)->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'amount' => $p->amount ?? null,
                'currency' => $p->currency ?? null,
                'status' => $p->status ?? null,
                'method' => $p->method ?? ($p->provider ?? null),
                'created_at' => $p->created_at->toIso8601String(),
            ]);

        return Inertia::render('Technique/Ministere', [
            'ministry' => [
                'id' => $ministry->id,
                'name' => $ministry->name,
                'subscription_status' => $ministry->subscription_status,
                'created_at' => $ministry->created_at->toIso8601String(),
                'org_units_count' => $orgUnitsCount,
                'members_count' => $membersCount,
            ],
            'users' => $users,
            'payments' => $payments,
        ]);
    }

    /** Nouveau mot de passe provisoire, affiche une seule fois a l'equipe technique. */
    public function resetPassword(User $user): RedirectResponse
    {
        $temporary = Str::lower(Str::random(4)).'-'.random_int(1000, 9999).'-'.Str::lower(Str::random(4));
        $user->forceFill(['password' => Hash::make($temporary)])->save();

        return back()->with('success', "Nouveau mot de passe provisoire de {$user->name} : {$temporary} (à lui transmettre, puis à changer depuis son profil). Ce message ne s'affiche qu'une fois.");
    }
}
