<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgendamentoParticipante extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agendamento_id',
        'nome',
        'cpf',
        'email',
        'data_nascimento',
        'telefone',
        'sexo',
        'vinculo',
        'turma',
        'origem',
        'observacoes',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
    ];

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }
}
