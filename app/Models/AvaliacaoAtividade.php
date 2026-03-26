<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvaliacaoAtividade extends Model
{
    protected $fillable = [
        'atividade_id',
        'user_id',
        'nome_educador',
        'qtd_participantes_prefeitura',
        'qtd_participantes_movimentos_sociais',
        'avaliacao_logistica',
        'avaliacao_acolhimento_sme',
        'avaliacao_recursos_materiais',
        'avaliacao_planejamento',
        'avaliacao_links_presenca',
        'avaliacao_destaques',
        'avaliacao_atuacao_equipe',
        'checklist_pos_acao',
    ];

    protected $casts = [
        'checklist_pos_acao' => 'array',
    ];

    public function atividade(): BelongsTo
    {
        return $this->belongsTo(Atividade::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}