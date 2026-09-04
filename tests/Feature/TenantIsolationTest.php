<?php

namespace Tests\Feature;

use App\Models\Ministry;
use App\Models\OrgUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Version PHPUnit du controle deja effectue manuellement en psql pendant
 * le developpement (voir database/sql_check/rls_test.sql) : a executer
 * une fois `composer install` possible (reseau indisponible dans cette
 * session de developpement, voir README).
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_ministere_ne_voit_jamais_les_org_units_dun_autre(): void
    {
        $ministryA = Ministry::create(['name' => 'RCV Benin', 'short_code' => 'RCV-BJ']);
        $ministryB = Ministry::create(['name' => 'Grace Assembly', 'short_code' => 'GA-CI']);

        $rootA = OrgUnit::create([
            'ministry_id' => $ministryA->id, 'parent_id' => null, 'level_rank' => 0,
            'level_label' => 'Ministère', 'name' => 'RCV Benin', 'code' => 'RACINE', 'path' => 'rcv_bj',
        ]);
        OrgUnit::create([
            'ministry_id' => $ministryB->id, 'parent_id' => null, 'level_rank' => 0,
            'level_label' => 'Ministère', 'name' => 'Grace Assembly', 'code' => 'RACINE', 'path' => 'ga_ci',
        ]);

        DB::statement('SELECT set_config(?, ?, false)', ['app.current_ministry_id', $ministryA->id]);

        $this->assertCount(1, OrgUnit::all());
        $this->assertSame($rootA->id, OrgUnit::first()->id);
    }

    public function test_sans_contexte_de_tenant_aucune_ligne_nest_visible(): void
    {
        $ministryA = Ministry::create(['name' => 'RCV Benin', 'short_code' => 'RCV-BJ']);
        OrgUnit::create([
            'ministry_id' => $ministryA->id, 'parent_id' => null, 'level_rank' => 0,
            'level_label' => 'Ministère', 'name' => 'RCV Benin', 'code' => 'RACINE', 'path' => 'rcv_bj',
        ]);

        DB::statement("SELECT set_config('app.current_ministry_id', '', false)");

        $this->assertCount(0, OrgUnit::all());
    }
}
