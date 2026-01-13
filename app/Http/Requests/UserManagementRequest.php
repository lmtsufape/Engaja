<?php

namespace App\Http\Requests;

use App\Models\Participante;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasAnyRole(['administrador', 'gestor']);
    }

    protected function prepareForValidation(): void
    {
        $toNull = fn($v) => ($v === '' || $v === null) ? null : $v;

        $cpfDigits = preg_replace('/\D+/', '', (string)($this->cpf ?? ''));
        $telDigits = preg_replace('/\D+/', '', (string)($this->telefone ?? ''));

        $this->merge([
            'name'             => isset($this->name) ? trim((string)$this->name) : null,
            'email'            => isset($this->email) ? trim((string)$this->email) : null,
            'role'             => isset($this->role) ? trim((string)$this->role) : null,
            'cpf'              => $toNull($cpfDigits ?: null),
            'telefone'         => $toNull($telDigits ?: null),
            'municipio_id'     => $toNull($this->municipio_id ?? null),
            'escola_unidade'   => $toNull(isset($this->escola_unidade) ? trim((string)$this->escola_unidade) : null),
            'tipo_organizacao' => $toNull(isset($this->tipo_organizacao) ? trim((string)$this->tipo_organizacao) : null),
            'tag'              => $toNull(isset($this->tag) ? trim((string)$this->tag) : null),
        ]);
    }

    public function rules(): array
    {
        $managedUser = $this->route('managedUser');
        $managedUserId = $managedUser?->id;

        return [
            'name'  => ['required','string','max:255'],
            'email' => [
                'required','email','max:255',
                Rule::unique('users','email')->ignore($managedUserId),
            ],
            'role'  => ['nullable','string', Rule::in($this->assignableRoleNames())],

            'cpf'              => ['nullable','digits:11'],
            'telefone'         => ['nullable','regex:/^\\d{10,11}$/'],
            'municipio_id'     => ['nullable','exists:municipios,id'],
            'escola_unidade'   => ['nullable','string','max:255'],
            'tipo_organizacao' => ['nullable','string','max:255', Rule::in(config('engaja.organizacoes', []))],
            'tag'              => ['nullable', Rule::in(Participante::TAGS)],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $cpf = $this->normalizeCpf($this->input('cpf'));
            if ($cpf) {
                if (!$this->isValidCpf($cpf)) {
                    $v->errors()->add('cpf', 'CPF invalido.');
                } else {
                    $managedUser = $this->route('managedUser');
                    $ignoreParticipanteId = $managedUser?->participante?->id;
                    if ($this->cpfDuplicado($cpf, $ignoreParticipanteId)) {
                        $v->errors()->add('cpf', 'Este CPF já possui cadastro no sistema.');
                    }
                }
            }

            $tel = (string)($this->input('telefone'));
            if ($tel && !preg_match('/^\\d{10,11}$/', $tel)) {
                $v->errors()->add('telefone', 'Telefone invalido. Use DDD + numero (10 ou 11 digitos).');
            }
        });
    }


    private function normalizeCpf(?string $cpf): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) ($cpf ?? ''));
        return $digits !== '' ? $digits : null;
    }

    private function cpfDuplicado(string $cpf, ?int $ignorarId = null): bool
    {
        $cpf = $this->normalizeCpf($cpf);
        if (! $cpf) {
            return false;
        }

        return Participante::query()
            ->whereNotNull('cpf')
            ->when($ignorarId, fn($q) => $q->where('id', '!=', $ignorarId))
            ->whereRaw("regexp_replace(cpf, '[^0-9]', '', 'g') = ?", [$cpf])
            ->exists();
    }

    private function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D+/', '', $cpf ?? '');
        if (strlen($cpf) !== 11) return false;
        if (preg_match('/^(\\d)\\1{10}$/', $cpf)) return false;

        $sum = 0;
        for ($i = 0, $w = 10; $i < 9; $i++, $w--) {
            $sum += (int) $cpf[$i] * $w;
        }
        $r = $sum % 11;
        $dv1 = ($r < 2) ? 0 : 11 - $r;

        $sum = 0;
        for ($i = 0, $w = 11; $i < 10; $i++, $w--) {
            $sum += (int) $cpf[$i] * $w;
        }
        $r = $sum % 11;
        $dv2 = ($r < 2) ? 0 : 11 - $r;

        return ($cpf[9] == $dv1) && ($cpf[10] == $dv2);
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Informe o nome do usuario.',
            'email.required'      => 'Informe o e-mail.',
            'email.email'         => 'Informe um e-mail valido.',
            'email.unique'        => 'Este e-mail ja esta em uso.',
            'role.in'             => 'O papel selecionado nao e permitido.',
            'cpf.required'        => 'CPF e obrigatorio.',
            'cpf.digits'          => 'CPF deve conter 11 digitos.',
            'telefone.regex'      => 'Telefone deve ter DDD e 10 ou 11 digitos.',
            'municipio_id.exists' => 'Municipio invalido.',
            'tipo_organizacao.in' => 'Selecione um tipo de organizacao valido.',
            'tag.in'              => 'Selecione uma tag valida.',
        ];
    }

    private function assignableRoleNames(): array
    {
        return Role::whereNotIn('name', ['administrador', 'gestor'])
            ->pluck('name')
            ->toArray();
    }
}
