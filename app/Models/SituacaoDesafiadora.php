<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SituacaoDesafiadora extends Model
{
    protected $table = 'situacoes_desafiadoras';

    protected $fillable = ['id', 'categoria', 'nome', 'descricao'];

    public function eventos(): BelongsToMany
    {
        return $this->belongsToMany(
            Evento::class,
            'evento_situacao_desafiadora',
            'situacao_desafiadora_id',
            'evento_id'
        )->withTimestamps();
    }
}