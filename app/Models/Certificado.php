<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificado extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'modelo_certificado_id',
        'participante_id',
        'evento_nome',
        'codigo_validacao',
        'ano',
        'texto_frente',
        'texto_verso',
        /** Total em minutos (nome legado da coluna). */
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

    public function eventos()
    {
        return $this->belongsToMany(Evento::class, 'certificado_evento');
    }
}
