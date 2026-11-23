<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dimensao extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['descricao'];

    public function indicadores(): HasMany
    {
        return $this->hasMany(Indicador::class);
    }
}
