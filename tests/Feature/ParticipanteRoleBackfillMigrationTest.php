<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParticipanteRoleBackfillMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_corrige_somente_engaja_sem_role_e_preserva_todos_os_vinculos_existentes(): void
    {
        $withoutRole = User::factory()->create();
        $withoutProfile = User::factory()->create();
        $withoutProfile->participante()->forceDelete();
        $deleted = User::factory()->create();
        $deleted->delete();
        $cartasWithoutRole = User::factory()->create(['sistema_origem' => User::SISTEMA_CARTAS]);

        $admin = User::factory()->create();
        $admin->assignRole(['administrador', 'gerente']);
        $participant = User::factory()->create();
        $participant->assignRole('participante');
        $cartas = User::factory()->create(['sistema_origem' => User::SISTEMA_CARTAS]);
        $cartas->assignRole('cartas_voluntario');
        $otherGuard = User::factory()->create();
        $apiRole = Role::create(['name' => 'externa', 'guard_name' => 'api']);
        DB::table('model_has_roles')->insert([
            'role_id' => $apiRole->id,
            'model_type' => $otherGuard->getMorphClass(),
            'model_id' => $otherGuard->id,
        ]);

        $untouchedIds = [$admin->id, $participant->id, $cartas->id, $otherGuard->id, $cartasWithoutRole->id];
        $before = DB::table('model_has_roles')->whereIn('model_id', $untouchedIds)
            ->orderBy('model_id')->orderBy('role_id')->get()->all();

        $this->migration()->up();

        foreach ([$withoutRole, $withoutProfile, $deleted] as $user) {
            $fresh = User::withTrashed()->findOrFail($user->id);
            $this->assertSame(['participante'], $fresh->getRoleNames()->all());
        }
        $this->assertEquals(
            $before,
            DB::table('model_has_roles')->whereIn('model_id', $untouchedIds)
                ->orderBy('model_id')->orderBy('role_id')->get()->all()
        );
        $this->assertCount(0, $cartasWithoutRole->fresh()->roles);
        $this->assertSoftDeleted($deleted);
        $this->assertSame(8, DB::table('model_has_roles')->count());
    }

    public function test_reexecucao_nao_duplica_roles_e_rollback_nao_revoga_acesso(): void
    {
        $user = User::factory()->create();
        $migration = $this->migration();
        $migration->up();
        $before = DB::table('model_has_roles')->get()->all();

        $migration->up();
        $migration->down();

        $this->assertEquals($before, DB::table('model_has_roles')->get()->all());
        $this->assertTrue($user->fresh()->hasRole('participante'));
    }

    public function test_ausencia_da_role_interrompe_correcao_sem_gravar_vinculos(): void
    {
        Role::findByName('participante', 'web')->delete();
        $user = User::factory()->create();

        try {
            $this->migration()->up();
            $this->fail('A correção deve falhar quando a role participante não existe.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('A role participante do guard web precisa existir', $exception->getMessage());
        }

        $this->assertCount(0, $user->fresh()->roles);
        $this->assertSame(0, DB::table('model_has_roles')->count());
    }

    public function test_base_nova_sem_usuarios_nao_exige_roles_previamente_semeadas(): void
    {
        Role::findByName('participante', 'web')->delete();

        $this->migration()->up();

        $this->assertSame(0, DB::table('model_has_roles')->count());
        $this->assertFalse(Role::where('name', 'participante')->exists());
    }

    public function test_bloqueia_escritas_concorrentes_nos_vinculos_durante_correcao(): void
    {
        User::factory()->create();
        $this->migration()->up();
        config(['database.connections.role_backfill_lock_test' => DB::connection()->getConfig()]);
        $other = DB::connection('role_backfill_lock_test');
        $other->beginTransaction();

        try {
            $other->statement('LOCK TABLE model_has_roles IN ROW EXCLUSIVE MODE NOWAIT');
            $this->fail('Alterações concorrentes de roles devem aguardar a correção.');
        } catch (QueryException $exception) {
            $this->assertSame('55P03', $exception->errorInfo[0]);
        } finally {
            $other->rollBack();
            DB::purge('role_backfill_lock_test');
        }
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_05_000001_assign_participante_role_to_engaja_users_without_roles.php');
    }
}
