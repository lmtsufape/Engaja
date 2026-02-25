<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MatrizAprendizagem extends Model
{
    protected $table = 'matrizes_aprendizagem';

    protected $fillable = ['id', 'nome', 'descricao'];

    public function eventos(): BelongsToMany
    {
        return $this->belongsToMany(
            Evento::class,
            'evento_matriz_aprendizagem',
            'matriz_aprendizagem_id',
            'evento_id'
        )->withTimestamps();
    }
}