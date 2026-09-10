<?php

namespace Tests\Feature;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\Ministry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Version PHPUnit du controle deja effectue manuellement en psql pendant
 * le developpement de l'assistant IA (voir
 * database/sql_check/assistant_rls_test.sql) - a executer une fois
 * `composer install` possible (reseau indisponible dans cette session de
 * developpement, meme limitation deja rencontree pour TenantIsolationTest).
 */
class AssistantTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_ministere_ne_voit_jamais_les_conversations_dun_autre(): void
    {
        $ministryA = Ministry::create(['name' => 'RCV Benin', 'short_code' => 'RCV-BJ']);
        $ministryB = Ministry::create(['name' => 'Grace Assembly', 'short_code' => 'GA-CI']);

        $userA = User::create(['name' => 'Pasteur A', 'email' => 'a@rcv.test', 'password' => 'x']);
        $userB = User::create(['name' => 'Pasteur B', 'email' => 'b@ga.test', 'password' => 'x']);

        DB::statement('SELECT set_config(?, ?, false)', ['app.current_ministry_id', $ministryA->id]);
        $conversationA = AssistantConversation::create(['ministry_id' => $ministryA->id, 'user_id' => $userA->id]);
        AssistantMessage::create(['ministry_id' => $ministryA->id, 'conversation_id' => $conversationA->id, 'role' => 'user', 'content' => 'Question A']);

        DB::statement('SELECT set_config(?, ?, false)', ['app.current_ministry_id', $ministryB->id]);
        AssistantConversation::create(['ministry_id' => $ministryB->id, 'user_id' => $userB->id]);

        DB::statement('SELECT set_config(?, ?, false)', ['app.current_ministry_id', $ministryA->id]);

        $this->assertCount(1, AssistantConversation::all());
        $this->assertSame($userA->id, AssistantConversation::first()->user_id);
        $this->assertCount(1, AssistantMessage::all());
    }

    public function test_sans_contexte_de_tenant_aucune_conversation_nest_visible(): void
    {
        $ministryA = Ministry::create(['name' => 'RCV Benin', 'short_code' => 'RCV-BJ']);
        $userA = User::create(['name' => 'Pasteur A', 'email' => 'a@rcv.test', 'password' => 'x']);

        DB::statement('SELECT set_config(?, ?, false)', ['app.current_ministry_id', $ministryA->id]);
        AssistantConversation::create(['ministry_id' => $ministryA->id, 'user_id' => $userA->id]);

        DB::statement("SELECT set_config('app.current_ministry_id', '', false)");

        $this->assertCount(0, AssistantConversation::all());
    }
}
