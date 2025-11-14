<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RespostaAvaliacao extends Model
{
    protected $fillable = [
        'avaliacao_id',
        'avaliacao_questao_id',
        'inscricao_id',
        'resposta',
    ];

    public function avaliacao(): BelongsTo
    {
        return $this->belongsTo(Avaliacao::class);
    }

    public function avaliacaoQuestao(): BelongsTo
    {
        return $this->belongsTo(AvaliacaoQuestao::class);
    }

    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }
}
