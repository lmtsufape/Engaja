<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserManagementRequest;
use App\Models\Municipio;
use App\Models\Participante;
use App\Models\User;
use App\Models\ModeloCertificado;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class UserManagementController extends Controller
{
    private const PROTECTED_ROLES = ['administrador'];

    private const LEGACY_ROLES = ['gestor', 'formador'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::with(['roles', 'participante'])
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', self::PROTECTED_ROLES))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->appends(['q' => $search]);

        return view('usuarios.index', [
            'users' => $users,
            'search' => $search,
            'modelosCertificado' => ModeloCertificado::orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function edit(User $managedUser): View|RedirectResponse
    {
        if ($this->isProtected($managedUser)) {
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'Este usuario nao pode ser editado.');
        }

        $managedUser->load(['participante.municipio.estado', 'roles']);

        $municipios = Municipio::with('estado')
            ->orderBy('nome')
            ->get(['id', 'nome', 'estado_id']);

        $organizacoes = config('engaja.organizacoes', []);
        $participanteTags = config('engaja.participante_tags', Participante::TAGS);
        $roles = $this->assignableRoles();

        return view('usuarios.edit', [
            'user'             => $managedUser,
            'municipios'       => $municipios,
            'organizacoes'     => $organizacoes,
            'participanteTags' => $participanteTags,
            'roles'            => $roles,
            'currentRole'      => $managedUser->roles->first()?->name,
        ]);
    }

    public function update(UserManagementRequest $request, User $managedUser): RedirectResponse
    {
        if ($this->isProtected($managedUser)) {
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'Este usuario nao pode ser editado.');
        }

        $data = $request->validated();

        $oldEmail = $managedUser->email;
        $managedUser->fill([
            'name'  => $data['name'],
            'email' => $data['email'],

            //campos demograficos
            'identidade_genero'            => $data['identidade_genero'] ?? null,
            'identidade_genero_outro'      => $data['identidade_genero_outro'] ?? null,
            'raca_cor'                     => $data['raca_cor'] ?? null,
            'comunidade_tradicional'       => $data['comunidade_tradicional'] ?? null,
            'comunidade_tradicional_outro' => $data['comunidade_tradicional_outro'] ?? null,
            'faixa_etaria'                 => $data['faixa_etaria'] ?? null,
            'pcd'                          => $data['pcd'] ?? null,
            'orientacao_sexual'            => $data['orientacao_sexual'] ?? null,
            'orientacao_sexual_outra'      => $data['orientacao_sexual_outra'] ?? null,
        ]);

        if ($oldEmail !== $data['email']) {
            $managedUser->email_verified_at = null;
        }

        $managedUser->save();

        $managedUser->participante()->updateOrCreate(
            ['user_id' => $managedUser->id],
            [
                'cpf'              => $data['cpf']              ?? null,
                'telefone'         => $data['telefone']         ?? null,
                'municipio_id'     => $data['municipio_id']     ?? null,
                'escola_unidade'   => $data['escola_unidade']   ?? null,
                'tipo_organizacao' => $data['tipo_organizacao'] ?? null,
                'tag'              => $data['tag']              ?? null,
            ]
        );

        if (auth()->user()->hasRole('administrador')) {
            //se a role vier preenchida no request, aplica. Se vier vazia, remove os acessos.
            $roleToApply = $data['role'] ?? null;

            if ($roleToApply) {
                $managedUser->syncRoles([$roleToApply]);
            } else {
                $managedUser->syncRoles([]);
            }
        }

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario atualizado com sucesso.');
    }

    private function assignableRoles()
    {
        $rolesToExclude = array_merge(self::PROTECTED_ROLES, self::LEGACY_ROLES);

        return Role::whereNotIn('name', $rolesToExclude)
            ->orderBy('name')
            ->get(['name']);
    }

    private function isProtected(User $user): bool
    {
        return $user->hasAnyRole(self::PROTECTED_ROLES);
    }

    public function export()
    {
        return Excel::download(new UsersExport, 'usuarios.xlsx');
    }
}
