<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $municipios = Municipio::with('estado')
            ->orderBy('nome')
            ->get(['id', 'nome', 'estado_id']);

        $participanteTags = config('engaja.participante_tags', Participante::TAGS);

        return view('auth.register', compact('municipios', 'participanteTags'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($this->prepareData($request), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'cpf'                          => ['required', 'digits:11'],
            'telefone'                     => ['nullable', 'regex:/^\d{10,11}$/'],
            'municipio_id'                 => ['nullable', 'exists:municipios,id'],
            'escola_unidade'               => ['nullable', 'string', 'max:255'],
            'tipo_organizacao'             => ['nullable', 'string', 'max:255', Rule::in(config('engaja.organizacoes', []))],
            'tag'                          => ['nullable', Rule::in(Participante::TAGS)],
            'identidade_genero'            => ['required', 'string'],
            'identidade_genero_outro'      => ['nullable', 'string', 'max:255', 'required_if:identidade_genero,Outro'],
            'raca_cor'                     => ['required', 'string'],
            'comunidade_tradicional'       => ['required', 'string'],
            'comunidade_tradicional_outro' => ['nullable', 'string', 'max:255', 'required_if:comunidade_tradicional,Outro'],
            'faixa_etaria'                 => ['required', 'string'],
            'pcd'                          => ['required', 'string'],
            'orientacao_sexual'            => ['required', 'string'],
            'orientacao_sexual_outra'      => ['nullable', 'string', 'max:255', 'required_if:orientacao_sexual,Outra'],
        ], [
            'cpf.required'                 => 'O campo CPF é obrigatório.',
            'cpf.digits'                   => 'CPF deve conter 11 dígitos.',
            'telefone.regex'               => 'Telefone deve ter DDD e 10 ou 11 dígitos.',
            'municipio_id.exists'          => 'Município inválido.',
            'tipo_organizacao.in'          => 'Selecione um tipo de organização válido.',
            'tag.in'                       => 'Selecione uma tag válida.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $cpf = $this->normalizeCpf($request->input('cpf'));
            if ($cpf && ! $this->isValidCpf($cpf)) {
                $validator->errors()->add('cpf', 'CPF inválido.');
                return;
            }

            if ($cpf && $this->cpfDuplicado($cpf)) {
                $validator->errors()->add('cpf', 'Este CPF já possui cadastro no sistema.');
            }
        });

        $data = $validator->validate();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'identidade_genero'            => $data['identidade_genero'],
                'identidade_genero_outro'      => $data['identidade_genero_outro'] ?? null,
                'raca_cor'                     => $data['raca_cor'],
                'comunidade_tradicional'       => $data['comunidade_tradicional'],
                'comunidade_tradicional_outro' => $data['comunidade_tradicional_outro'] ?? null,
                'faixa_etaria'                 => $data['faixa_etaria'],
                'pcd'                          => $data['pcd'],
                'orientacao_sexual'            => $data['orientacao_sexual'],
                'orientacao_sexual_outra'      => $data['orientacao_sexual_outra'] ?? null,
            ]);

            $user->participante()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'cpf' => $data['cpf'],
                    'telefone' => $data['telefone'] ?? null,
                    'municipio_id' => $data['municipio_id'] ?? null,
                    'escola_unidade' => $data['escola_unidade'] ?? null,
                    'tipo_organizacao' => $data['tipo_organizacao'] ?? null,
                    'tag' => $data['tag'] ?? null,
                ]
            );

            $user->assignRole('participante');

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect('/');
    }

    private function prepareData(Request $request): array
    {
        $toNull = fn ($value) => ($value === '' || $value === null) ? null : $value;

        return array_merge($request->all(), [
            'name' => isset($request->name) ? trim((string) $request->name) : null,
            'email' => isset($request->email) ? trim((string) $request->email) : null,
            'cpf' => $toNull(preg_replace('/\D+/', '', (string) ($request->cpf ?? '')) ?: null),
            'telefone' => $toNull(preg_replace('/\D+/', '', (string) ($request->telefone ?? '')) ?: null),
            'municipio_id' => $toNull($request->municipio_id ?? null),
            'escola_unidade' => $toNull(isset($request->escola_unidade) ? trim((string) $request->escola_unidade) : null),
            'tipo_organizacao' => $toNull(isset($request->tipo_organizacao) ? trim((string) $request->tipo_organizacao) : null),
            'tag' => $toNull(isset($request->tag) ? trim((string) $request->tag) : null),
        ]);
    }

    private function normalizeCpf(?string $cpf): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) ($cpf ?? ''));

        return $digits !== '' ? $digits : null;
    }

    private function cpfDuplicado(string $cpf): bool
    {
        $cpf = $this->normalizeCpf($cpf);
        if (! $cpf) {
            return false;
        }

        return Participante::query()
            ->whereNotNull('cpf')
            ->whereRaw("regexp_replace(cpf, '[^0-9]', '', 'g') = ?", [$cpf])
            ->exists();
    }

    private function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D+/', '', $cpf ?? '');
        if (strlen($cpf) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        $sum = 0;
        for ($i = 0, $weight = 10; $i < 9; $i++, $weight--) {
            $sum += (int) $cpf[$i] * $weight;
        }
        $remainder = $sum % 11;
        $digitOne = ($remainder < 2) ? 0 : 11 - $remainder;

        $sum = 0;
        for ($i = 0, $weight = 11; $i < 10; $i++, $weight--) {
            $sum += (int) $cpf[$i] * $weight;
        }
        $remainder = $sum % 11;
        $digitTwo = ($remainder < 2) ? 0 : 11 - $remainder;

        return ($cpf[9] == $digitOne) && ($cpf[10] == $digitTwo);
    }
}
