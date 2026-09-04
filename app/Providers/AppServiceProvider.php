<?php

namespace App\Providers;

use App\Models\OrgUnit;
use App\Policies\OrgUnitPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Droits en cascade par sous-arbre (point 05) - voir OrgUnitPolicy.
        Gate::policy(OrgUnit::class, OrgUnitPolicy::class);
    }
}
