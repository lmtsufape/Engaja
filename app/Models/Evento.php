<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Evento extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'eixo_id',
        'nome',
        'tipo',
        'data_horario',
        'duracao',
        'modalidade',
        'link',
        'objetivo',
        'resumo',
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
}
