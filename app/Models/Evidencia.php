<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evidencia extends Model
{
    protected $fillable = [
        'indicador_id',
        'descricao',
    ];

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class);
    }

    public function questoes(): HasMany
    {
        return $this->hasMany(Questao::class);
    }
}

