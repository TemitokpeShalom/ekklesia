<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AttachmentCodeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;

// Invitation : acceptation ouverte a une personne pas encore connectee.
Route::get('/invitations/{token}', [InvitationController::class, 'acceptShow'])->name('invitations.accept.show');
Route::post('/invitations/{token}', [InvitationController::class, 'acceptStore'])->name('invitations.accept.store');

// Rattachement d'un nouveau noeud : ouvert (le code lui-meme est la preuve
// de mandat, point 03), mais la creation du compte associe passe par une
// invitation separee (point 11).
Route::post('/rattachement', [AttachmentCodeController::class, 'redeem'])->name('attachment-codes.redeem');

Route::get('/connexion', [LoginController::class, 'create'])->name('login');
Route::post('/connexion', [LoginController::class, 'store']);

Route::middleware(['auth', 'tenant.context'])->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/org-units/{orgUnit}', [DashboardController::class, 'show'])->name('dashboard');

    Route::get('/org-units/{orgUnit}/code-de-rattachement', [AttachmentCodeController::class, 'create'])
        ->name('attachment-codes.create');
    Route::post('/org-units/{orgUnit}/code-de-rattachement', [AttachmentCodeController::class, 'store'])
        ->name('attachment-codes.store');

    Route::get('/org-units/{orgUnit}/inviter', [InvitationController::class, 'create'])->name('invitations.create');
    Route::post('/org-units/{orgUnit}/inviter', [InvitationController::class, 'store'])->name('invitations.store');
});
