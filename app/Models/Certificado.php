<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificado extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'modelo_certificado_id',
        'participante_id',
        'evento_nome',
        'codigo_validacao',
        'ano',
        'texto_frente',
        'texto_verso',
        'carga_horaria',
    ];

    public function modelo()
    {
        return $this->belongsTo(ModeloCertificado::class, 'modelo_certificado_id');
    }

    public function participante()
    {
        return $this->belongsTo(Participante::class);
    }
}
