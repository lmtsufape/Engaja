<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AtividadeAcao extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'atividade_acoes';

    protected $fillable = [
        'nome',
        'detalhe',
        'usa_turmas',
        'turmas',
    ];

    protected $casts = [
        'usa_turmas' => 'boolean',
        'turmas' => 'array',
    ];

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function getTurmasConfiguradasAttribute(): array
    {
        $turmas = is_array($this->turmas) ? $this->turmas : [];

        return collect($turmas)
            ->map(fn ($turma) => trim((string) $turma))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
