<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Atividade extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['evento_id', 'dia', 'hora_inicio', 'carga_horaria'];

    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }
}

