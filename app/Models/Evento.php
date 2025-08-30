<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    protected $fillable = [
        'user_id', 'eixo_id', 'nome', 'tipo', 'data_horario',
        'duracao', 'modalidade', 'link', 'objetivo', 'resumo'
    ];

    public function eixo() { return $this->belongsTo(Eixo::class); }
    public function user() { return $this->belongsTo(User::class); }
}
