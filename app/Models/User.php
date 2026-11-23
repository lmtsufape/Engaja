<?php

namespace App\Models;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaEvento;
use App\Models\Cartas\CartaMensagem;
use App\Notifications\Cartas\CartasVerifyEmailNotification;
use App\Notifications\CartasResetPasswordNotification;
use Database\Factories\UserFactory;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    public const SISTEMA_ENGAJA = 'engaja';

    public const SISTEMA_CARTAS = 'cartas';

    public const VINCULO_PETROBRAS = 'petrobras';

    public const VINCULO_COMUNIDADE = 'comunidade_freiriana';

    public const VINCULOS_CARTAS = [
        self::VINCULO_PETROBRAS => 'Funcionário Petrobras',
        self::VINCULO_COMUNIDADE => 'Comunidade Freiriana',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'sistema_origem',
        'cartas_terms_accepted_at',
        'cartas_welcome_seen_at',
        'cartas_limite_respostas',
        'cartas_tipo_vinculo',
        'force_password_change',
        'profile_photo_path',
        'identidade_genero',
        'identidade_genero_outro',
        'raca_cor',
        'comunidade_tradicional',
        'comunidade_tradicional_outro',
        'faixa_etaria',
        'pcd',
        'orientacao_sexual',
        'orientacao_sexual_outra',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'cartas_terms_accepted_at' => 'datetime',
            'cartas_welcome_seen_at' => 'datetime',
            'cartas_limite_respostas' => 'integer',
            'password' => 'hashed',
            'force_password_change' => 'boolean',
        ];
    }

    public function isCartasUser(): bool
    {
        return $this->sistema_origem === self::SISTEMA_CARTAS;
    }

    public function isEngajaUser(): bool
    {
        return ! $this->isCartasUser();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify($this->isCartasUser()
            ? new CartasResetPasswordNotification($token)
            : new ResetPassword($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify($this->isCartasUser()
            ? new CartasVerifyEmailNotification
            : new VerifyEmail);
    }

    public function participante()
    {
        return $this->hasOne(Participante::class, 'user_id');
    }

    public function cartasComoVoluntario()
    {
        return $this->hasMany(Carta::class, 'voluntario_user_id');
    }

    public function cartaMensagensComoRemetente()
    {
        return $this->hasMany(CartaMensagem::class, 'remetente_user_id');
    }

    public function cartaMensagensComoDestinatario()
    {
        return $this->hasMany(CartaMensagem::class, 'destinatario_user_id');
    }

    public function cartaMensagensVerificadas()
    {
        return $this->hasMany(CartaMensagem::class, 'verificada_por');
    }

    public function cartaEventos()
    {
        return $this->hasMany(CartaEvento::class, 'user_id');
    }

    public function eventos()
    {
        return $this->hasMany(Evento::class);
    }

    public function agendamentos()
    {
        return $this->hasMany(Agendamento::class);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        return '/storage/'.ltrim($this->profile_photo_path, '/');
    }

    public function getProfileInitialAttribute(): string
    {
        $name = trim((string) ($this->name ?? ''));

        if ($name === '') {
            return 'U';
        }

        return mb_strtoupper(mb_substr($name, 0, 1));
    }

    public function getNomeComLocalidadeAttribute(): string
    {
        $estado = $this->participante?->municipio?->estado?->sigla;
        $municipio = $this->participante?->municipio?->nome;

        return collect([$this->name, $municipio, $estado])
            ->filter()
            ->implode(' - ');
    }

    public function getNomeAttribute(): string
    {
        return $this->name ?? 'Usuário';
    }

    public function getMunicipioEstadoAttribute(): string
    {
        if (! $this->participante?->municipio) {
            return 'Não informado';
        }

        $municipio = $this->participante->municipio->nome;
        $estado = $this->participante->municipio->estado?->nome ?? $this->participante->municipio->estado?->sigla ?? '';

        return collect([$municipio, $estado])
            ->filter()
            ->implode(' - ');
    }

    /**
     * Verifica se todos os campos demográficos obrigatórios estão preenchidos.
     */
    public function demograficosCompletos(): bool
    {
        $camposObrigatorios = [
            'identidade_genero',
            'raca_cor',
            'comunidade_tradicional',
            'faixa_etaria',
            'pcd',
            'orientacao_sexual',
        ];

        return ! collect($camposObrigatorios)
            ->some(fn ($campo) => empty($this->$campo));
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            $user->participante()->firstOrCreate(['user_id' => $user->id], [
                'cpf' => null,
                'telefone' => null,
                'municipio_id' => null,
                'escola_unidade' => null,
                'tipo_organizacao' => null,
                'tag' => null,
            ]);
        });
    }
}
