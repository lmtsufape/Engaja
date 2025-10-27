<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inscricao extends Model
{
    use SoftDeletes;

    protected $fillable = ['evento_id', 'atividade_id', 'participante_id'];

    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }

    public function atividade()
    {
        return $this->belongsTo(Atividade::class);
    }
    public function participante()
    {
        return $this->belongsTo(Participante::class);
    }
    public function presencas()
    {
        return $this->hasMany(Presenca::class);
    }
}
