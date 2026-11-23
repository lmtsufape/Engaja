<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $rolesTable = config('permission.table_names.roles');
            $pivotTable = config('permission.table_names.model_has_roles');
            $roleKey = config('permission.column_names.role_pivot_key') ?? 'role_id';
            $modelKey = config('permission.column_names.model_morph_key');
            $modelType = (new User)->getMorphClass();

            // Impede alterações de roles entre a seleção e a atribuição dos acessos.
            $quotedPivot = DB::connection()->getQueryGrammar()->wrapTable($pivotTable);
            DB::statement('LOCK TABLE '.$quotedPivot.' IN SHARE ROW EXCLUSIVE MODE');

            $users = DB::table('users')
                ->where('sistema_origem', User::SISTEMA_ENGAJA)
                ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')
                    ->from($pivotTable)
                    ->whereColumn($pivotTable.'.'.$modelKey, 'users.id')
                    ->where($pivotTable.'.model_type', $modelType));

            if (! (clone $users)->exists()) {
                return;
            }

            $roleId = DB::table($rolesTable)
                ->where('name', 'participante')
                ->where('guard_name', 'web')
                ->value('id');

            if ($roleId === null) {
                throw new RuntimeException('A role participante do guard web precisa existir antes da correção dos usuários.');
            }

            DB::table($pivotTable)->insertUsing(
                [$roleKey, 'model_type', $modelKey],
                $users->selectRaw('CAST(? AS bigint), CAST(? AS varchar), users.id', [$roleId, $modelType])
            );
        });
    }

    public function down(): void
    {
        // A correção de dados é permanente: o rollback não deve revogar acessos.
    }
};
