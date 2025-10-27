<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participante extends Model
{
    use SoftDeletes;

    public const TAG_REDE_ENSINO = 'Rede de Ensino';
    public const TAG_MOVIMENTO_SOCIAL = 'Movimento Social';

    public const TAGS = [
        self::TAG_REDE_ENSINO,
        self::TAG_MOVIMENTO_SOCIAL,
    ];

    protected $table = 'participantes';

    protected $fillable = ['user_id', 'municipio_id', 'cpf', 'telefone', 'escola_unidade', 'tag', 'data_entrada'];

    protected $appends = ['cpf_valido'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function inscricoes()
    {
        return $this->hasMany(Inscricao::class, 'participante_id');
    }

    public function eventos()
    {
        return $this->belongsToMany(Evento::class, 'inscricaos')
            ->withPivot(['atividade_id'])
            ->withTimestamps();
    }

    public function getCpfValidoAttribute()
    {
        return $this->validaCpf($this->cpf);
    }

    private function validaCpf($cpf)
    {
        // Aqui você coloca a regra de validação de CPF
        // (ou usa um package como "laravel-legends/pt-br-validator")
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }
}
