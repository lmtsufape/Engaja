<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvaliacaoQuestao extends Model
{
    protected $table = 'avaliacao_questoes';

    protected $fillable = [
        'avaliacao_id',
        'questao_id',
        'indicador_id',
        'escala_id',
        'evidencia_id',
        'texto',
        'tipo',
        'ordem',
        'fixa',
    ];

    public function avaliacao(): BelongsTo
    {
        return $this->belongsTo(Avaliacao::class);
    }

    public function questaoOriginal(): BelongsTo
    {
        return $this->belongsTo(Questao::class, 'questao_id');
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class);
    }

    public function escala(): BelongsTo
    {
        return $this->belongsTo(Escala::class);
    }

    public function evidencia(): BelongsTo
    {
        return $this->belongsTo(Evidencia::class);
    }
}
