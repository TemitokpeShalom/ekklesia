<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ActivityReportController;
use App\Http\Controllers\AffectationsController;
use App\Http\Controllers\OrgUnitTransformationController;
use App\Http\Controllers\AnnouncementsController;
use App\Http\Controllers\AssetsController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AttachmentCodeController;
use App\Http\Controllers\BibliothequeController;
use App\Http\Controllers\CultesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscipleshipController;
use App\Http\Controllers\DocumentGeneratorController;
use App\Http\Controllers\FinanceReportController;
use App\Http\Controllers\FinanceTransactionsController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HonorificTitlesController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\MinistryInfoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SacramentsController;
use App\Http\Controllers\SignalementsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionCryptoController;
use App\Http\Controllers\SubscriptionFedapayController;
use App\Http\Controllers\TeamMembersController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\TrombinoscopeController;
use App\Http\Controllers\MinistryRegistrationController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

// Page d'accueil publique (2026-09-09, voir WelcomeController) : c'est
// desormais elle, et non plus /connexion, que redirectGuestsTo
// (bootstrap/app.php) presente a un visiteur non connecte.
Route::get('/bienvenue', [WelcomeController::class, 'index'])->name('welcome');

// Creation libre-service d'un nouveau ministere (tenant), sans validation
// manuelle - voir MinistryRegistrationController. Ouvert a quiconque, comme
// /rattachement et /invitations/{token} : forcement hors du groupe
// auth/tenant.context ci-dessous, puisque la personne n'a pas encore de
// compte au moment ou elle remplit ce formulaire.
Route::get('/ministeres/nouveau', [MinistryRegistrationController::class, 'create'])->name('ministries.create');
Route::post('/ministeres/nouveau', [MinistryRegistrationController::class, 'store'])->name('ministries.store');

// Invitation : acceptation ouverte a une personne pas encore connectee.
Route::get('/invitations/{token}', [InvitationController::class, 'acceptShow'])->name('invitations.accept.show');
Route::post('/invitations/{token}', [InvitationController::class, 'acceptStore'])->name('invitations.accept.store');

// Rattachement d'un nouveau noeud : ouvert a quiconque possede le code (le
// code lui-meme est la preuve de mandat, point 03) - avec ou sans compte
// Oikonema existant. Le formulaire (page GET) cree le compte de la
// personne dans le meme geste si elle n'en a pas encore, comme pour une
// invitation (point 11) ; voir AttachmentCodeController::redeem.
Route::get('/rattachement', [AttachmentCodeController::class, 'redeemShow'])->name('attachment-codes.redeem.show');
Route::post('/rattachement', [AttachmentCodeController::class, 'redeem'])->name('attachment-codes.redeem');

Route::get('/connexion', [LoginController::class, 'create'])->name('login');
Route::post('/connexion', [LoginController::class, 'store']);

// Point 15 : notification serveur-a-serveur envoyee par FedaPay lui-meme,
// jamais par un navigateur authentifie - forcement hors du groupe
// auth/tenant.context, et exclue de la verification CSRF (voir
// bootstrap/app.php) puisque FedaPay ne peut fournir aucun jeton CSRF. La
// signature (X-FEDAPAY-SIGNATURE) en tient lieu, verifiee dans le
// controleur lui-meme avant toute ecriture.
Route::post('/webhooks/fedapay', [SubscriptionFedapayController::class, 'webhook'])->name('webhooks.fedapay');

Route::middleware(['auth', 'tenant.context'])->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');

    // Point 09 : le logo de l'en-tete (AppLayout.vue) y renvoie des qu'aucun
    // orgUnit n'est en contexte (ecran Aide notamment) - jusqu'ici sans
    // route associee, donc une 404 a chaque clic depuis ces ecrans.
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/org-units/{orgUnit}', [DashboardController::class, 'show'])->name('dashboard');

    Route::get('/org-units/{orgUnit}/code-de-rattachement', [AttachmentCodeController::class, 'create'])
        ->name('attachment-codes.create');
    Route::post('/org-units/{orgUnit}/code-de-rattachement', [AttachmentCodeController::class, 'store'])
        ->name('attachment-codes.store');

    Route::get('/org-units/{orgUnit}/inviter', [InvitationController::class, 'create'])->name('invitations.create');
    Route::post('/org-units/{orgUnit}/inviter', [InvitationController::class, 'store'])->name('invitations.store');

    Route::get('/org-units/{orgUnit}/acces', [AffectationsController::class, 'index'])->name('affectations.index');
    Route::delete('/org-units/{orgUnit}/acces/{affectation}', [AffectationsController::class, 'destroy'])->name('affectations.destroy');

    Route::get('/org-units/{orgUnit}/transformation', [OrgUnitTransformationController::class, 'create'])->name('org-units.transform.create');
    Route::post('/org-units/{orgUnit}/transformation', [OrgUnitTransformationController::class, 'store'])->name('org-units.transform.store');

    // Manuel d'utilisation integre (point 09), recherche comprise (point 17) :
    // contenu global, non lie a un OrgUnit precis, accessible depuis
    // n'importe quel contexte connecte. Anciennement deux ecrans separes
    // ("Aide" et "Assistant") pour le meme contenu - fusionnes le
    // 2026-09-09 (voir HelpController).
    Route::get('/aide', [HelpController::class, 'index'])->name('help.index');
    Route::get('/aide/{slug}', [HelpController::class, 'show'])->name('help.show');

    // "Mon profil" (v4 "Constellation", 2026-09-10, voir ProfileController) :
    // comme /aide, non lie a un OrgUnit - une personne modifie son propre
    // compte quel que soit le ministere ou elle se trouve.
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/mot-de-passe', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Assistant IA integre (chantier du 2026-09-10, "autres corrections"
    // point 1) : API JSON pour le widget flottant (AssistantWidget.vue),
    // present sur toutes les pages authentifiees - jamais de visite
    // Inertia ici, uniquement des appels fetch en arriere-plan. Voir
    // AssistantController pour le detail de l'isolation par utilisateur
    // (en plus de la RLS par ministere, deja active par tenant.context).
    Route::prefix('assistant')->name('assistant.')->group(function () {
        Route::get('/conversation', [AssistantController::class, 'show'])->name('show');
        Route::post('/conversation/nouvelle', [AssistantController::class, 'startNew'])->name('new');
        Route::post('/conversation/{conversation}/messages', [AssistantController::class, 'sendMessage'])->name('messages.store');
        Route::post('/conversation/{conversation}/signaler', [AssistantController::class, 'flagFeedback'])->name('feedback');
    });

    Route::get('/org-units/{orgUnit}/membres', [MembersController::class, 'index'])->name('members.index');
    Route::get('/org-units/{orgUnit}/membres/nouveau', [MembersController::class, 'create'])->name('members.create');
    Route::post('/org-units/{orgUnit}/membres', [MembersController::class, 'store'])->name('members.store');
    Route::get('/org-units/{orgUnit}/membres/{member}/modifier', [MembersController::class, 'edit'])->name('members.edit');
    Route::put('/org-units/{orgUnit}/membres/{member}', [MembersController::class, 'update'])->name('members.update');
    Route::delete('/org-units/{orgUnit}/membres/{member}', [MembersController::class, 'destroy'])->name('members.destroy');

    // Titres honorifiques configurables (reliquat du point 08) : reglage
    // unique par ministere, ecran reserve a la racine de l'arbre.
    Route::get('/org-units/{orgUnit}/titres-honorifiques', [HonorificTitlesController::class, 'edit'])->name('honorific-titles.edit');
    Route::put('/org-units/{orgUnit}/titres-honorifiques', [HonorificTitlesController::class, 'update'])->name('honorific-titles.update');

    // Informations officielles du ministere (2026-09-09) : sigle, siege,
    // coordonnees, numero d'autorisation, logo - renseignables des la
    // creation (voir MinistryRegistrationController) mais completables ici
    // ensuite. Meme droit et meme reserve a la racine que ci-dessus.
    Route::get('/org-units/{orgUnit}/informations-ministere', [MinistryInfoController::class, 'edit'])->name('ministry-info.edit');
    Route::put('/org-units/{orgUnit}/informations-ministere', [MinistryInfoController::class, 'update'])->name('ministry-info.update');

    // Abonnement et facturation (point 15) : reglage unique par ministere,
    // ecran reserve a la racine de l'arbre - meme droit que les titres
    // honorifiques et la gouvernance des acces ci-dessus. Reforme du
    // 2026-09-10 : plus aucune auto-activation sans preuve de paiement -
    // seuls les deux moyens de paiement verifies ci-dessous (FedaPay,
    // crypto) peuvent desormais activer un abonnement (voir
    // SubscriptionController, la route 'update' a ete retiree).
    Route::get('/org-units/{orgUnit}/abonnement', [SubscriptionController::class, 'edit'])->name('subscription.edit');
    Route::post('/org-units/{orgUnit}/abonnement/fedapay', [SubscriptionFedapayController::class, 'checkout'])->name('subscription.fedapay.checkout');
    Route::post('/org-units/{orgUnit}/abonnement/crypto', [SubscriptionCryptoController::class, 'store'])->name('subscription.crypto.store');

    Route::get('/org-units/{orgUnit}/trombinoscope', [TrombinoscopeController::class, 'index'])->name('trombinoscope.index');

    // Generateur de documents (reliquat du point 08) : hub + gabarits
    // affiche/calendrier, le trombinoscope ci-dessus restant sur sa propre
    // route deja en place.
    Route::get('/org-units/{orgUnit}/documents', [DocumentGeneratorController::class, 'index'])->name('documents.index');
    Route::get('/org-units/{orgUnit}/documents/{template}', [DocumentGeneratorController::class, 'show'])->name('documents.show');

    Route::get('/org-units/{orgUnit}/cultes', [CultesController::class, 'index'])->name('cultes.index');
    Route::get('/org-units/{orgUnit}/cultes/nouveau', [CultesController::class, 'create'])->name('cultes.create');
    Route::post('/org-units/{orgUnit}/cultes', [CultesController::class, 'store'])->name('cultes.store');
    Route::get('/org-units/{orgUnit}/cultes/{culte}/modifier', [CultesController::class, 'edit'])->name('cultes.edit');
    Route::put('/org-units/{orgUnit}/cultes/{culte}', [CultesController::class, 'update'])->name('cultes.update');
    Route::delete('/org-units/{orgUnit}/cultes/{culte}', [CultesController::class, 'destroy'])->name('cultes.destroy');

    Route::get('/org-units/{orgUnit}/parcours', [DiscipleshipController::class, 'index'])->name('discipleship.index');
    Route::get('/org-units/{orgUnit}/parcours/nouveau', [DiscipleshipController::class, 'create'])->name('discipleship.create');
    Route::post('/org-units/{orgUnit}/parcours', [DiscipleshipController::class, 'store'])->name('discipleship.store');
    Route::get('/org-units/{orgUnit}/parcours/{etape}/modifier', [DiscipleshipController::class, 'edit'])->name('discipleship.edit');
    Route::put('/org-units/{orgUnit}/parcours/{etape}', [DiscipleshipController::class, 'update'])->name('discipleship.update');
    Route::delete('/org-units/{orgUnit}/parcours/{etape}', [DiscipleshipController::class, 'destroy'])->name('discipleship.destroy');

    Route::get('/org-units/{orgUnit}/sacrements', [SacramentsController::class, 'index'])->name('sacrements.index');
    Route::get('/org-units/{orgUnit}/sacrements/nouveau', [SacramentsController::class, 'create'])->name('sacrements.create');
    Route::post('/org-units/{orgUnit}/sacrements', [SacramentsController::class, 'store'])->name('sacrements.store');
    Route::get('/org-units/{orgUnit}/sacrements/{sacrement}/modifier', [SacramentsController::class, 'edit'])->name('sacrements.edit');
    Route::put('/org-units/{orgUnit}/sacrements/{sacrement}', [SacramentsController::class, 'update'])->name('sacrements.update');
    Route::delete('/org-units/{orgUnit}/sacrements/{sacrement}', [SacramentsController::class, 'destroy'])->name('sacrements.destroy');

    Route::get('/org-units/{orgUnit}/equipes', [TeamsController::class, 'index'])->name('teams.index');
    Route::get('/org-units/{orgUnit}/equipes/nouvelle', [TeamsController::class, 'create'])->name('teams.create');
    Route::post('/org-units/{orgUnit}/equipes', [TeamsController::class, 'store'])->name('teams.store');
    Route::get('/org-units/{orgUnit}/equipes/{equipe}/modifier', [TeamsController::class, 'edit'])->name('teams.edit');
    Route::put('/org-units/{orgUnit}/equipes/{equipe}', [TeamsController::class, 'update'])->name('teams.update');
    Route::delete('/org-units/{orgUnit}/equipes/{equipe}', [TeamsController::class, 'destroy'])->name('teams.destroy');
    Route::post('/org-units/{orgUnit}/equipes/{equipe}/membres', [TeamMembersController::class, 'store'])->name('team-members.store');
    Route::delete('/org-units/{orgUnit}/equipes/{equipe}/membres/{membre}', [TeamMembersController::class, 'destroy'])->name('team-members.destroy');

    Route::get('/org-units/{orgUnit}/bibliotheque', [BibliothequeController::class, 'index'])->name('bibliotheque.index');

    Route::get('/org-units/{orgUnit}/finances', [FinanceTransactionsController::class, 'index'])->name('finances.index');
    Route::get('/org-units/{orgUnit}/finances/nouveau', [FinanceTransactionsController::class, 'create'])->name('finances.create');
    Route::post('/org-units/{orgUnit}/finances', [FinanceTransactionsController::class, 'store'])->name('finances.store');
    Route::get('/org-units/{orgUnit}/finances/{transaction}/modifier', [FinanceTransactionsController::class, 'edit'])->name('finances.edit');
    Route::put('/org-units/{orgUnit}/finances/{transaction}', [FinanceTransactionsController::class, 'update'])->name('finances.update');
    Route::delete('/org-units/{orgUnit}/finances/{transaction}', [FinanceTransactionsController::class, 'destroy'])->name('finances.destroy');

    Route::get('/org-units/{orgUnit}/finances-rapport', [FinanceReportController::class, 'show'])->name('finances.rapport');

    Route::get('/org-units/{orgUnit}/rapport-activites', [ActivityReportController::class, 'edit'])->name('activites.rapport');
    Route::post('/org-units/{orgUnit}/rapport-activites', [ActivityReportController::class, 'update'])->name('activites.update');

    Route::get('/org-units/{orgUnit}/inventaire', [AssetsController::class, 'index'])->name('inventaire.index');
    Route::get('/org-units/{orgUnit}/inventaire/nouveau', [AssetsController::class, 'create'])->name('inventaire.create');
    Route::post('/org-units/{orgUnit}/inventaire', [AssetsController::class, 'store'])->name('inventaire.store');
    Route::get('/org-units/{orgUnit}/inventaire/{asset}/modifier', [AssetsController::class, 'edit'])->name('inventaire.edit');
    Route::put('/org-units/{orgUnit}/inventaire/{asset}', [AssetsController::class, 'update'])->name('inventaire.update');
    Route::delete('/org-units/{orgUnit}/inventaire/{asset}', [AssetsController::class, 'destroy'])->name('inventaire.destroy');
    Route::get('/org-units/{orgUnit}/inventaire-rapport', [AssetsController::class, 'rapport'])->name('inventaire.rapport');

    Route::get('/org-units/{orgUnit}/annonces', [AnnouncementsController::class, 'index'])->name('annonces.index');
    Route::get('/org-units/{orgUnit}/annonces/nouvelle', [AnnouncementsController::class, 'create'])->name('annonces.create');
    Route::post('/org-units/{orgUnit}/annonces', [AnnouncementsController::class, 'store'])->name('annonces.store');
    Route::get('/org-units/{orgUnit}/annonces/{announcement}/modifier', [AnnouncementsController::class, 'edit'])->name('annonces.edit');
    Route::put('/org-units/{orgUnit}/annonces/{announcement}', [AnnouncementsController::class, 'update'])->name('annonces.update');
    Route::delete('/org-units/{orgUnit}/annonces/{announcement}', [AnnouncementsController::class, 'destroy'])->name('annonces.destroy');
    Route::post('/org-units/{orgUnit}/annonces/{announcement}/lu', [AnnouncementsController::class, 'markRead'])->name('annonces.lu');

    Route::get('/org-units/{orgUnit}/signalements', [SignalementsController::class, 'index'])->name('signalements.index');
    Route::post('/org-units/{orgUnit}/signalements', [SignalementsController::class, 'store'])->name('signalements.store');
    Route::put('/org-units/{orgUnit}/signalements/{signalement}', [SignalementsController::class, 'updateStatus'])->name('signalements.update');
});
