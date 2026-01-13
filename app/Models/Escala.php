<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Escala extends Model
{
    protected $fillable = [
        'descricao',
        'opcao1',
        'opcao2',
        'opcao3',
        'opcao4',
        'opcao5',
    ];

    public function getValoresAttribute(): array
    {
        return collect([
            $this->opcao1,
            $this->opcao2,
            $this->opcao3,
            $this->opcao4,
            $this->opcao5,
        ])->filter()->values()->all();
    }
}
