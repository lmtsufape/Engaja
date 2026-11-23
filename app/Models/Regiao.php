<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Regiao extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const PRIORITARIAS_IDS = [1, 2, 3];

    public const PRIORITARIAS_NOMES = ['Norte', 'Nordeste I', 'Nordeste II'];

    protected $table = 'regiaos';

    protected $fillable = ['nome'];

    public function estados()
    {
        return $this->hasMany(Estado::class);
    }

    public function isPrioritaria(): bool
    {
        return in_array($this->id, self::PRIORITARIAS_IDS, true) || in_array($this->nome, self::PRIORITARIAS_NOMES, true);
    }
}
