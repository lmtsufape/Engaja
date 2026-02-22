<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiDimensaoValor extends Model
{
    protected $table = 'bi_dimensao_valores';
    protected $fillable = ['dimensao_id', 'codigo'];

    public function dimensao()
    {
        return $this->belongsTo(BiDimensao::class, 'dimensao_id');
    }}
