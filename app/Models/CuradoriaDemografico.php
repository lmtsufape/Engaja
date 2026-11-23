<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuradoriaDemografico extends Model
{
    protected $fillable = [
        'user_id',
        'identidade_genero',
        'identidade_genero_outro',
        'raca_cor',
        'comunidade_tradicional',
        'comunidade_tradicional_outro',
        'faixa_etaria',
        'pcd',
        'orientacao_sexual',
        'orientacao_sexual_outra',
        'vinculado',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
