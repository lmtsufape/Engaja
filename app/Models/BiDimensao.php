<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiDimensao extends Model
{
    protected $table = 'bi_dimensoes';
    protected $fillable = ['codigo'];

    public function valores()
    {
        return $this->hasMany(BiDimensaoValor::class, 'dimensao_id');
    }}
