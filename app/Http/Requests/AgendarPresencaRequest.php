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
        return AgendamentoPresenca::regras(fechamentoNoFuturo: true);
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
                if (! $this->filled('presenca_abre_em') && ! $this->filled('presenca_fecha_em')) {
                    $validator->errors()->add('presenca_abre_em', 'Informe ao menos um horário de abertura ou de fechamento.');
                }
            },
        ];
    }

    /** @return array{presenca_abre_em: ?Carbon, presenca_fecha_em: ?Carbon} */
    public function horariosUtc(): array
    {
        return [
            'presenca_abre_em' => AgendamentoPresenca::paraUtc($this->validated('presenca_abre_em')),
            'presenca_fecha_em' => AgendamentoPresenca::paraUtc($this->validated('presenca_fecha_em')),
        ];
    }
}
