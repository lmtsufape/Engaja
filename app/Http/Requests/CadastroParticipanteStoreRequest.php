<?php

namespace App\Http\Requests;

use App\Models\Participante;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CadastroParticipanteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // normaliza strings + vazios -> null
        $toNull = fn($v) => ($v === '' || $v === null) ? null : $v;

        // remove máscara e guarda apenas dígitos
        $cpfDigits = preg_replace('/\D+/', '', (string)($this->cpf ?? ''));
        $telDigits = preg_replace('/\D+/', '', (string)($this->telefone ?? ''));

        $this->merge([
            'name'           => isset($this->name) ? trim((string)$this->name) : null,
            'email'          => isset($this->email) ? trim((string)$this->email) : null,
            'cpf'            => $toNull($cpfDigits ?: null),
            'telefone'       => $toNull($telDigits ?: null),
            'municipio_id'   => $toNull($this->municipio_id ?? null),
            'escola_unidade' => $toNull(isset($this->escola_unidade) ? trim((string)$this->escola_unidade) : null),
            'tag'            => $toNull(isset($this->tag) ? trim((string)$this->tag) : null),
            'data_entrada'   => $toNull($this->data_entrada ?? null),
        ]);
    }

    public function rules(): array
    {
        return [
            'name'  => ['required','string','max:255'],
            'email' => [
                'required','email','max:255',
                Rule::unique('users','email'),
            ],

            'cpf'            => ['nullable','digits:11'],
            'telefone'       => ['nullable','regex:/^\d{10,11}$/'],
            'municipio_id'   => ['nullable','exists:municipios,id'],
            'escola_unidade' => ['nullable','string','max:255'],
            'tag'            => ['nullable', Rule::in(Participante::TAGS)],
            'data_entrada'   => ['nullable','date'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $cpf = (string)($this->input('cpf'));
            if ($cpf) {
                if (!$this->isValidCpf($cpf)) {
                    $v->errors()->add('cpf', 'CPF inválido.');
                }
            }

            $tel = (string)($this->input('telefone'));
            if ($tel && !preg_match('/^\d{10,11}$/', $tel)) {
                $v->errors()->add('telefone', 'Telefone inválido. Use DDD + número (10 ou 11 dígitos).');
            }
        });
    }

    private function isValidCpf(string $cpf): bool
    {
        // só dígitos
        $cpf = preg_replace('/\D+/', '', $cpf ?? '');
        if (strlen($cpf) !== 11) return false;

        // elimina CPFs repetidos
        if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;

        // cálculo DV1
        $sum = 0;
        for ($i=0, $w=10; $i<9; $i++, $w--) $sum += (int)$cpf[$i] * $w;
        $r = $sum % 11;
        $dv1 = ($r < 2) ? 0 : 11 - $r;

        // cálculo DV2
        $sum = 0;
        for ($i=0, $w=11; $i<10; $i++, $w--) $sum += (int)$cpf[$i] * $w;
        $r = $sum % 11;
        $dv2 = ($r < 2) ? 0 : 11 - $r;

        return ($cpf[9] == $dv1) && ($cpf[10] == $dv2);
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Informe seu nome.',
            'email.required'      => 'Informe seu e-mail.',
            'email.email'         => 'Informe um e-mail válido.',
            'email.unique'        => 'Este e-mail já está em uso.',
            'cpf.digits'          => 'CPF deve conter 11 dígitos.',
            'telefone.regex'      => 'Telefone deve ter DDD e 10 ou 11 dígitos.',
            'municipio_id.exists' => 'Município inválido.',
            'tag.in'              => 'Selecione uma tag válida.',
            'data_entrada.date'   => 'Data de entrada inválida.',
        ];
    }
}
