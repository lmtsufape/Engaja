<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateAvaliacao extends Model
{
    protected $table = 'template_avaliacaos';

    protected $fillable = ['nome', 'descricao'];

    public function questoes(): HasMany
    {
        return $this->hasMany(Questao::class)
            ->orderBy('ordem')
            ->orderBy('id');
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class);
    }
}
