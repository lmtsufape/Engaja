<?php

namespace App\Models;

use App\Models\Participante;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, hasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
            'password' => 'hashed',
        ];
    }

    public function participante()
    {
        return $this->hasOne(Participante::class, 'user_id');
    }

    public function eventos()
    {
        return $this->hasMany(Evento::class);
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            $user->participante()->firstOrCreate(['user_id' => $user->id], [
                'cpf'            => null,
                'telefone'       => null,
                'municipio_id'   => null,
                'escola_unidade' => null,
                'tag' => null,
            ]);
        });
    }
}


