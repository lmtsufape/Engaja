<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presenca extends Model
{
    use SoftDeletes;

    protected $fillable = ['inscricao_id', 'atividade_id', 'status', 'justificativa', 'avaliacao_respondida', 'certificado_emitido'];

    /**
     * Presenças que ainda não geraram certificado.
     */
    public function scopeSemCertificado($query)
    {
        return $query->where('certificado_emitido', false);
    }

    public function inscricao()
    {
        return $this->belongsTo(Inscricao::class);
    }

    public function atividade()
    {
        return $this->belongsTo(Atividade::class);
    }
}
