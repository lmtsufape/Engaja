<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Atividade extends Model
{
    use HasFactory;

    protected $fillable = ['evento_id', 'dia', 'hora_inicio', 'carga_horaria'];

    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }
}

