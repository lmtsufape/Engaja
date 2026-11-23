<?php

namespace App\Models\Cartas;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartaEvento extends Model
{
    public const UPDATED_AT = null;

    public const TIPO_CRIADA = 'criada';

    public const TIPO_DISTRIBUIDA = 'distribuida';

    public const TIPO_MENSAGEM_ENVIADA = 'mensagem_enviada';

    public const TIPO_MENSAGEM_VERIFICADA = 'mensagem_verificada';

    public const TIPO_AJUSTE_SOLICITADO = 'ajuste_solicitado';

    public const TIPO_EDITADA_ADMIN = 'editada_admin';

    public const TIPO_ENCERRADA = 'encerrada';

    protected $fillable = [
        'carta_id',
        'carta_mensagem_id',
        'user_id',
        'tipo',
        'dados_antes',
        'dados_depois',
    ];

    protected function casts(): array
    {
        return [
            'dados_antes' => 'array',
            'dados_depois' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function carta(): BelongsTo
    {
        return $this->belongsTo(Carta::class);
    }

    public function mensagem(): BelongsTo
    {
        return $this->belongsTo(CartaMensagem::class, 'carta_mensagem_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
