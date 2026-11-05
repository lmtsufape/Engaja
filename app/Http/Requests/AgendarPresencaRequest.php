<?php

namespace App\Http\Requests;

use App\Support\AgendamentoPresenca;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class AgendarPresencaRequest extends FormRequest
{
    /** Bag separado para a view reabrir o modal de agendamento quando houver erro. */
    protected $errorBag = 'agendamentoPresenca';

    public function authorize(): bool
    {
        return $this->user()?->can('presenca.abrir') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $regras = ['agendamento_presenca_ativo' => ['sometimes', 'boolean']];
        foreach (AgendamentoPresenca::regras(fechamentoNoFuturo: true) as $campo => $validacoes) {
            $regras[$campo] = ['exclude_if:agendamento_presenca_ativo,0', ...$validacoes];
        }

        return $regras;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return AgendamentoPresenca::mensagens();
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->exists('agendamento_presenca_ativo') && ! $this->boolean('agendamento_presenca_ativo')) {
                    return;
                }

                if (! $this->filled('presenca_abre_em') && ! $this->filled('presenca_fecha_em')) {
                    $validator->errors()->add('presenca_abre_em', 'Informe ao menos um horário de abertura ou de fechamento.');
                }
            },
        ];
    }

    /** @return array{presenca_abre_em: ?Carbon, presenca_fecha_em: ?Carbon} */
    public function horariosUtc(): array
    {
        if ($this->exists('agendamento_presenca_ativo') && ! $this->boolean('agendamento_presenca_ativo')) {
            return ['presenca_abre_em' => null, 'presenca_fecha_em' => null];
        }

        return [
            'presenca_abre_em' => AgendamentoPresenca::paraUtc($this->validated('presenca_abre_em')),
            'presenca_fecha_em' => AgendamentoPresenca::paraUtc($this->validated('presenca_fecha_em')),
        ];
    }
}
