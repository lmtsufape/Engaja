<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indicador extends Model
{
    protected $fillable = ['dimensao_id', 'descricao'];

    public function dimensao(): BelongsTo
    {
        return $this->belongsTo(Dimensao::class);
    }

    public function questoes(): HasMany
    {
        return $this->hasMany(Questao::class);
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(Evidencia::class);
    }
}
