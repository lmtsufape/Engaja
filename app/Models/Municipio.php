<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Municipio extends Model
{
    use SoftDeletes;
    protected $table = 'municipios';
    protected $fillable = [
        'estado_id',
        'nome',
        'interlocutor_email',
    ];
    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }
    
    public function getNomeComEstadoAttribute(): string
    {
        $sigla = $this->estado?->sigla; // << usa 'sigla'
        return trim($this->nome . ($sigla ? ' - ' . $sigla : ''));
    }
}
