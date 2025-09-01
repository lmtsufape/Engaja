<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Municipio extends Model
{
    use SoftDeletes;
    protected $table = 'municipios';
    protected $fillable = [
        'estado_id',
        'nome',
    ];
}
