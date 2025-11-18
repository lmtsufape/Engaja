<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'objetivo',
        'resumo',
        'local',
        'imagem',
    ];

    public function eixo()
    {
        return $this->belongsTo(Eixo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function atividades()
    {
        return $this->hasMany(Atividade::class);
    }

    public function inscricoes(){
        return $this->hasMany(Inscricao::class);
    }

    public function participantes(){
        return $this->belongsToMany(Participante::class, 'inscricaos')
            ->withPivot(['atividade_id'])
            ->withTimestamps();
    }
}
