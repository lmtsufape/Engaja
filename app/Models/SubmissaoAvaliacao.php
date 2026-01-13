<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissaoAvaliacao extends Model
{
    protected $table = 'submissao_avaliacoes';

    protected $fillable = [
        'codigo',
        'atividade_id',
        'avaliacao_id',
    ];

    public function atividade(): BelongsTo
    {
        return $this->belongsTo(Atividade::class);
    }

    public function avaliacao(): BelongsTo
    {
        return $this->belongsTo(Avaliacao::class);
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(RespostaAvaliacao::class);
    }
}
