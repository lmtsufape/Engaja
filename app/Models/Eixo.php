<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Eixo extends Model
{
    protected $fillable = ['nome'];

    public function eventos()
    {
        return $this->hasMany(Evento::class);
    }
}
