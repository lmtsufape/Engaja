<?php

namespace App\Models;

use App\Models\Cartas\Carta;
use App\Support\AgendamentoPresenca;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Atividade extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'evento_id',
        'municipio_id',
        'abrangencia_nacional',
        'descricao',
        'dia',
        'hora_inicio',
        'hora_fim',
        'publico_esperado',
        /** Minutos inteiros (nome legado da coluna). */
        'carga_horaria',
        'presenca_ativa',
        'presenca_abre_em',
        'presenca_fecha_em',
        'checklist_planejamento',
        'checklist_encerramento',
    ];

    protected $casts = [
        'abrangencia_nacional' => 'boolean',
        'presenca_ativa' => 'boolean',
        'presenca_abre_em' => 'datetime',
        'presenca_fecha_em' => 'datetime',
        'checklist_planejamento' => 'array',
        'checklist_encerramento' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Atividade $atividade) {
            // Se presenca_ativa não foi explicitamente fornecida ou for nula:
            // abre a presença por padrão (true), a menos que haja um horário de abertura agendado no futuro.
            if (! array_key_exists('presenca_ativa', $atividade->getAttributes()) || $atividade->presenca_ativa === null) {
                $temAberturaFutura = $atividade->presenca_abre_em?->isFuture() ?? false;
                $atividade->presenca_ativa = ! $temAberturaFutura;
            }
        });
    }

    /**
     * Estado efetivo da confirmação de presença, calculado no momento da chamada.
     *
     * Um horário de fechamento vencido tem prioridade sobre um de abertura vencido;
     * sem horários vencidos vale o estado manual (`presenca_ativa`).
     *
     * IMPORTANTE: use sempre este método em vez de ler `presenca_ativa` diretamente,
     * pois a coluna só é consolidada quando a presença do momento é alterada.
     */
    public function presencaEstaAberta(?CarbonInterface $agora = null): bool
    {
        $agora ??= now();

        if ($this->presenca_fecha_em && $this->presenca_fecha_em->lte($agora)) {
            return false;
        }

        if ($this->presenca_abre_em && $this->presenca_abre_em->lte($agora)) {
            return true;
        }

        return (bool) $this->presenca_ativa;
    }

    /**
     * Aplica (em memória) os horários agendados que já venceram: grava o estado
     * resultante em `presenca_ativa` e limpa os timestamps consumidos.
     * Quem chama é responsável por salvar o model.
     */
    public function consolidarAgendamentoPresenca(?CarbonInterface $agora = null): void
    {
        $agora ??= now();

        $abriu = $this->presenca_abre_em && $this->presenca_abre_em->lte($agora);
        $fechou = $this->presenca_fecha_em && $this->presenca_fecha_em->lte($agora);

        if (! $abriu && ! $fechou) {
            return;
        }

        $this->presenca_ativa = $this->presencaEstaAberta($agora);

        if ($abriu) {
            $this->presenca_abre_em = null;
        }

        if ($fechou) {
            $this->presenca_fecha_em = null;
        }
    }

    /** Próximo horário agendado ainda não vencido (abertura ou fechamento), se houver. */
    public function proximoAgendamentoPresenca(?CarbonInterface $agora = null): ?array
    {
        $agora ??= now();

        $proximos = collect([
            ['tipo' => 'abre', 'em' => $this->presenca_abre_em],
            ['tipo' => 'fecha', 'em' => $this->presenca_fecha_em],
        ])->filter(fn ($p) => $p['em'] && $p['em']->gt($agora))->sortBy(fn ($p) => $p['em']->getTimestamp());

        return $proximos->first();
    }

    /** Texto curto do status da presença para a interface (ex.: "Aberta · fecha em 06/10/2026 às 12:00"). */
    public function getStatusPresencaLabelAttribute(): string
    {
        $agora = now();
        $label = $this->presencaEstaAberta($agora) ? 'Aberta' : 'Fechada';

        $agendamentos = collect([
            ['verbo' => 'abre', 'em' => $this->presenca_abre_em],
            ['verbo' => 'fecha', 'em' => $this->presenca_fecha_em],
        ])
            ->filter(fn ($p) => $p['em'] && $p['em']->gt($agora))
            ->sortBy(fn ($p) => $p['em']->getTimestamp())
            ->map(fn ($p) => $p['verbo'].' em '.AgendamentoPresenca::formatar($p['em']));

        return $agendamentos->isEmpty() ? $label : $label.' · '.$agendamentos->implode(' · ');
    }

    public function getChecklistsIncompletosAttribute(): bool
    {
        $totalPlanejamento = 13;
        $totalEncerramento = 3;

        $pl = $this->checklist_planejamento ?? [];
        $en = $this->checklist_encerramento ?? [];

        $plIncompleto = count($pl) < $totalPlanejamento;
        $enIncompleto = count($en) < $totalEncerramento;

        return $plIncompleto || $enIncompleto;
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function municipios(): BelongsToMany
    {
        return $this->belongsToMany(Municipio::class, 'atividade_municipio')
            ->withTimestamps();
    }

    public function presencas()
    {
        return $this->hasMany(Presenca::class);
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class);
    }

    public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class);
    }

    public function cartas(): HasMany
    {
        return $this->hasMany(Carta::class);
    }

    public function participantes(): BelongsToMany
    {
        return $this->belongsToMany(Participante::class, 'inscricaos')
            ->withPivot(['evento_id'])
            ->withTimestamps();
    }

    public function avaliacaoAtividades(): HasMany
    {
        return $this->hasMany(AvaliacaoAtividade::class);
    }

    public function getMinhaAvaliacaoAtividadeAttribute(): ?AvaliacaoAtividade
    {
        $userId = auth()->id();
        if (! $userId) {
            return null;
        }

        if ($this->relationLoaded('avaliacaoAtividades')) {
            return $this->avaliacaoAtividades->firstWhere('user_id', $userId);
        }

        return $this->avaliacaoAtividades()->where('user_id', $userId)->first();
    }
}
