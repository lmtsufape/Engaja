<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Questao extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'indicador_id',
        'escala_id',
        'template_avaliacao_id',
        'evidencia_id',
        'texto',
        'tipo',
        'opcoes_resposta',
        'fixa',
        'ordem',
    ];

    protected $casts = [
        'fixa' => 'boolean',
        'opcoes_resposta' => 'array',
    ];

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class);
    }

    public function escala(): BelongsTo
    {
        return $this->belongsTo(Escala::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateAvaliacao::class, 'template_avaliacao_id');
    }

    public function evidencia(): BelongsTo
    {
        return $this->belongsTo(Evidencia::class);
    }
}
