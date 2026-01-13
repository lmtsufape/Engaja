<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RespostaAvaliacao extends Model
{
    protected $fillable = [
        'avaliacao_id',
        'avaliacao_questao_id',
        'submissao_avaliacao_id',
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

    public function submissaoAvaliacao(): BelongsTo
    {
        return $this->belongsTo(SubmissaoAvaliacao::class);
    }

}
