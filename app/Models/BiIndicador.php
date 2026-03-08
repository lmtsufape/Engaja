<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiIndicador extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Tipo de Valor
    |--------------------------------------------------------------------------
    |
    | ABSOLUTO   → Número inteiro (ex: 1532)
    | PERCENTUAL → Número decimal representando 0–100 (ex: 15.83)
    |
    | OBS:
    | Percentual é armazenado como 15.83 e não 0.1583.
    |
    */

    public const TIPO_ABSOLUTO   = 'ABSOLUTO';
    public const TIPO_PERCENTUAL = 'PERCENTUAL';

    public const TIPOS_ENUM = [
        self::TIPO_ABSOLUTO,
        self::TIPO_PERCENTUAL,
    ];

    protected $table = 'bi_indicadores';

    protected $fillable = [
        'codigo',
        'tipo_valor',
        'fenomeno_id'
    ];

    public function fenomeno()
    {
        return $this->belongsTo(BiFenomeno::class);
    }

    public function valores()
    {
        return $this->hasMany(BiValor::class);
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }
}
