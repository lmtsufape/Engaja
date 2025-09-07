<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Atividade extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['evento_id', 'descricao','dia', 'hora_inicio', 'hora_fim', 'presenca_ativa'];

    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }

    public function presencas()
    {
        return $this->hasMany(Presenca::class);
    }
}

