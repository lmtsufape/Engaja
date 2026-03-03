<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiFenomeno extends Model
{
    protected $fillable = ['codigo'];

    public function indicadores()
    {
        return $this->hasMany(BiIndicador::class);
    }
}
