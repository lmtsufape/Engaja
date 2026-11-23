<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agendamento extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'atividade_acao_id',
        'turma',
        'municipio_id',
        'user_id',
        'data_horario',
        'publico_participante',
        'local_acao',
        'efetivado',
        'efetivado_em',
        'atividade_id',
    ];

    protected $casts = [
        'data_horario' => 'datetime',
        'efetivado' => 'boolean',
        'efetivado_em' => 'datetime',
    ];

    public function atividadeAcao(): BelongsTo
    {
        return $this->belongsTo(AtividadeAcao::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function participantesClonados(): HasMany
    {
        return $this->hasMany(AgendamentoParticipante::class);
    }

    public function atividade(): BelongsTo
    {
        return $this->belongsTo(Atividade::class);
    }
}
