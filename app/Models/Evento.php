<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evento extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'eixo_id',
        'nome',
        'tipo',
        'data_horario',
        'duracao',
        'data_inicio',
        'data_fim',
        'modalidade',
        'link',
        'objetivos_gerais',
        'objetivos_especificos',
        'local',
        'imagem',
        'recursos_materiais_necessarios',
        'providencias_sme_parceria',
        'observacoes_complementares',
    ];

    public function eixo(): BelongsTo
    {
        return $this->belongsTo(Eixo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function atividades(): HasMany
    {
        return $this->hasMany(Atividade::class);
    }

    public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class);
    }

    public function participantes(): BelongsToMany
    {
        return $this->belongsToMany(Participante::class, 'inscricaos')
            ->withPivot(['atividade_id'])
            ->withTimestamps();
    }

    public function situacoesDesafiadoras(): BelongsToMany
    {
        return $this->belongsToMany(
            SituacaoDesafiadora::class,
            'evento_situacao_desafiadora',
            'evento_id',
            'situacao_desafiadora_id'
        )->withTimestamps();
    }

    public function matrizes(): BelongsToMany
    {
        return $this->belongsToMany(
            MatrizAprendizagem::class,
            'evento_matriz_aprendizagem',
            'evento_id',
            'matriz_aprendizagem_id'
        )->withTimestamps();
    }

    public function sequenciasDidaticas(): HasMany
    {
        return $this->hasMany(SequenciaDidatica::class, 'evento_id');
    }

    public function presencas()
    {
        return $this->hasManyThrough(
            Presenca::class,
            Atividade::class,
            'evento_id',
            'atividade_id',
            'id',
            'id'
        );
    }
}