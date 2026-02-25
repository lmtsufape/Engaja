<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SequenciaDidatica extends Model
{
    protected $table = 'sequencias_didaticas';

    protected $fillable = [
        'evento_id',
        'periodo',
        'descricao',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }
}