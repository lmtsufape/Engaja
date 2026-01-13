<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModeloCertificado extends Model
{
    use SoftDeletes;

    protected $table = 'modelo_certificados';

    protected $fillable = [
        'eixo_id',
        'nome',
        'descricao',
        'imagem_frente',
        'imagem_verso',
        'texto_frente',
        'texto_verso',
        'layout_frente',
        'layout_verso',
    ];

    protected $casts = [
        'layout_frente' => 'array',
        'layout_verso'  => 'array',
    ];

    public function eixo()
    {
        return $this->belongsTo(Eixo::class);
    }
}
