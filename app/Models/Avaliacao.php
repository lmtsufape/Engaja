<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Avaliacao extends Model
{
    protected $fillable = ['template_avaliacao_id', 'inscricao_id', 'atividade_id'];

    public function templateAvaliacao(): BelongsTo
    {
        return $this->belongsTo(TemplateAvaliacao::class);
    }

    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }

    public function atividade(): BelongsTo
    {
        return $this->belongsTo(Atividade::class);
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(RespostaAvaliacao::class);
    }

    public function avaliacaoQuestoes(): HasMany
    {
        return $this->hasMany(AvaliacaoQuestao::class)
            ->orderBy('ordem')
            ->orderBy('id');
    }
}
