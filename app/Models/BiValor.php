<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiValor extends Model
{
    protected $table = 'bi_valores';

    protected $fillable = [
        'valor',
        'ano',
        'municipio_id',
        'indicador_id',
        'dimensao_valor_id'
    ];

    public function indicador()
    {
        return $this->belongsTo(BiIndicador::class, 'indicador_id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }
}
