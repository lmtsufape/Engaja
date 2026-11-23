<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Agrega os quantitativos do painel gerencial (KPIs, metas por ação,
 * participação por região, segmentos, evolução semestral e listas de
 * acompanhamento). Centraliza a lógica consumida pela tela, pelo PDF e
 * pelo export XLSX.
 */
class PainelGerencialService
{
    /**
     * Monta o payload completo do painel aplicando os filtros do request.
     *
     * @return array{filtros: array, kpis: array, metas_por_acao: array, participacao_por_regiao: array, segmentos: array, evolucao_semestral: array, municipios_baixo_engajamento: array, eventos_sem_avaliacao: array, recorrencia_ausencia: array}
     */
    public function buildPayload(Request $request): array
    {
        $filtros = $this->extractFilters($request);

        return [
            'filtros' => $this->describeFilters($filtros),
            'kpis' => $this->kpis($filtros),
            'metas_por_acao' => $this->metasPorAcao($filtros),
            'participacao_por_regiao' => $this->participacaoPorRegiao($filtros),
            'segmentos' => $this->segmentos($filtros),
            'evolucao_semestral' => $this->evolucaoSemestral($filtros),
            'municipios_baixo_engajamento' => $this->municipiosBaixoEngajamento($filtros),
            'eventos_sem_avaliacao' => $this->eventosSemAvaliacao($filtros),
            'recorrencia_ausencia' => $this->recorrenciaAusencia($filtros),
        ];
    }

    /**
     * @return array{evento_id: ?int, municipio_id: ?int, regiao_id: ?int, de: ?Carbon, ate: ?Carbon, periodo: string}
     */
    private function extractFilters(Request $request): array
    {
        return [
            'evento_id' => $request->integer('evento_id') ?: null,
            'municipio_id' => $request->integer('municipio_id') ?: null,
            'regiao_id' => $request->integer('regiao_id') ?: null,
            'de' => $request->date('de'),
            'ate' => $request->date('ate'),
            'periodo' => (string) $request->get('periodo', ''),
        ];
    }

    /**
     * Aplica os filtros comuns a uma query que já contém a tabela `atividades`
     * (e os joins de eventos/municipios/estados/regiaos quando necessário).
     *
     * @param  array<string, mixed>  $f
     */
    private function applyAtividadeFilters(Builder $query, array $f, bool $hasRegiaoJoin = false): Builder
    {
        $query->when($f['evento_id'], fn ($q) => $q->where('atividades.evento_id', $f['evento_id']));
        $query->when($f['municipio_id'], fn ($q) => $q->where('atividades.municipio_id', $f['municipio_id']));
        if ($hasRegiaoJoin) {
            $query->when($f['regiao_id'], fn ($q) => $q->where('regiaos.id', $f['regiao_id']));
        }

        $query->when($f['de'] && $f['ate'], fn ($q) => $q->whereBetween('atividades.dia', [$f['de'], $f['ate']]));
        $query->when($f['de'] && ! $f['ate'], fn ($q) => $q->where('atividades.dia', '>=', $f['de']));
        $query->when(! $f['de'] && $f['ate'], fn ($q) => $q->where('atividades.dia', '<=', $f['ate']));

        $query->when($f['periodo'] === 'manha', fn ($q) => $q->whereRaw("CAST(atividades.hora_inicio AS time) < '12:00:00'"));
        $query->when($f['periodo'] === 'tarde', fn ($q) => $q->whereRaw("CAST(atividades.hora_inicio AS time) >= '12:00:00'")
            ->whereRaw("CAST(atividades.hora_inicio AS time) < '18:00:00'"));
        $query->when($f['periodo'] === 'noite', fn ($q) => $q->whereRaw("CAST(atividades.hora_inicio AS time) >= '18:00:00'"));

        return $query;
    }

    /**
     * Base de atividades válidas (não deletadas e vinculadas a um evento),
     * com joins geográficos, já filtrada.
     *
     * @param  array<string, mixed>  $f
     */
    private function baseAtividades(array $f): Builder
    {
        $query = DB::table('atividades')
            ->leftJoin('eventos', 'eventos.id', '=', 'atividades.evento_id')
            ->leftJoin('municipios', 'municipios.id', '=', 'atividades.municipio_id')
            ->leftJoin('estados', 'estados.id', '=', 'municipios.estado_id')
            ->leftJoin('regiaos', 'regiaos.id', '=', 'estados.regiao_id')
            ->whereNull('atividades.deleted_at')
            ->whereNotNull('atividades.evento_id');

        return $this->applyAtividadeFilters($query, $f, hasRegiaoJoin: true);
    }

    /**
     * Presenças com status 'presente' das atividades em escopo, já filtradas,
     * com os joins até participante/usuário.
     *
     * @param  array<string, mixed>  $f
     */
    private function basePresencas(array $f, string $status = 'presente'): Builder
    {
        $query = DB::table('presencas')
            ->join('atividades', 'presencas.atividade_id', '=', 'atividades.id')
            ->join('inscricaos', 'presencas.inscricao_id', '=', 'inscricaos.id')
            ->join('participantes', 'inscricaos.participante_id', '=', 'participantes.id')
            ->leftJoin('municipios', 'municipios.id', '=', 'atividades.municipio_id')
            ->leftJoin('estados', 'estados.id', '=', 'municipios.estado_id')
            ->leftJoin('regiaos', 'regiaos.id', '=', 'estados.regiao_id')
            ->whereNull('atividades.deleted_at')
            ->whereNotNull('atividades.evento_id')
            ->when($status !== '', fn ($q) => $q->where('presencas.status', $status));

        return $this->applyAtividadeFilters($query, $f, hasRegiaoJoin: true);
    }

    /**
     * @param  array<string, mixed>  $f
     */
    private function kpis(array $f): array
    {
        $municipiosAtivos = (clone $this->baseAtividades($f))
            ->whereNotNull('atividades.municipio_id')
            ->distinct()
            ->count('atividades.municipio_id');

        $eventosRealizados = (clone $this->baseAtividades($f))
            ->distinct()
            ->count('atividades.evento_id');

        // Participantes: totais = presenças; únicos = participantes distintos.
        $participantesTotais = (clone $this->basePresencas($f))->count();
        $participantesUnicos = (clone $this->basePresencas($f))->distinct()->count('participantes.id');

        $avaliacoesRespondidas = (clone $this->basePresencas($f))
            ->where('presencas.avaliacao_respondida', true)
            ->count();

        $certificadosEmitidos = (clone $this->basePresencas($f))
            ->where('presencas.certificado_emitido', true)
            ->count();

        // Pendências de documentação: participantes presentes sem CPF/telefone.
        $pendencias = (clone $this->basePresencas($f))
            ->where(function ($q) {
                $q->whereNull('participantes.cpf')->orWhere('participantes.cpf', '')
                    ->orWhereNull('participantes.telefone')->orWhere('participantes.telefone', '');
            })
            ->distinct()
            ->count('participantes.id');

        // Horas cumpridas por modalidade (carga_horaria em minutos → horas).
        $buckets = config('painel-gerencial.modalidade_buckets');
        $horasRows = (clone $this->baseAtividades($f))
            ->selectRaw('eventos.modalidade, SUM(atividades.carga_horaria) as minutos')
            ->groupBy('eventos.modalidade')
            ->get();

        $horas = ['presencial' => 0.0, 'ead' => 0.0, 'hibrido' => 0.0, 'outros' => 0.0];
        foreach ($horasRows as $row) {
            $bucket = $buckets[$row->modalidade] ?? 'outros';
            $horas[$bucket] += round(((int) $row->minutos) / 60, 1);
        }

        return [
            'municipios_ativos' => $municipiosAtivos,
            'participantes_totais' => $participantesTotais,
            'participantes_unicos' => $participantesUnicos,
            'eventos_realizados' => $eventosRealizados,
            'horas_presenciais' => round($horas['presencial'], 1),
            'horas_ead' => round($horas['ead'], 1),
            'horas_hibrido' => round($horas['hibrido'], 1),
            'certificados_emitidos' => $certificadosEmitidos,
            'avaliacoes_respondidas' => $avaliacoesRespondidas,
            'pendencias_documentacao' => $pendencias,
        ];
    }

    /**
     * Previstas × inscritos × presentes × avaliações por Ação Pedagógica.
     *
     * @param  array<string, mixed>  $f
     */
    private function metasPorAcao(array $f): array
    {
        // Previstas (soma de publico_esperado das atividades em escopo) por evento.
        $previstas = (clone $this->baseAtividades($f))
            ->selectRaw('atividades.evento_id, eventos.nome as evento_nome, SUM(atividades.publico_esperado) as previstas')
            ->groupBy('atividades.evento_id', 'eventos.nome')
            ->get()
            ->keyBy('evento_id');

        // Inscritos distintos por evento (vinculados às atividades em escopo).
        $inscritos = (clone $this->baseAtividades($f))
            ->join('inscricaos', 'inscricaos.atividade_id', '=', 'atividades.id')
            ->selectRaw('atividades.evento_id, COUNT(DISTINCT inscricaos.participante_id) as inscritos')
            ->groupBy('atividades.evento_id')
            ->pluck('inscritos', 'evento_id');

        // Presentes e avaliações por evento.
        $presencas = (clone $this->basePresencas($f))
            ->selectRaw('atividades.evento_id')
            ->selectRaw('COUNT(*) as presentes')
            ->selectRaw('COUNT(CASE WHEN presencas.avaliacao_respondida = true THEN 1 END) as avaliacoes')
            ->groupBy('atividades.evento_id')
            ->get()
            ->keyBy('evento_id');

        $rows = [];
        foreach ($previstas as $eventoId => $prev) {
            $prevQtd = (int) $prev->previstas;
            $presentes = (int) ($presencas->get($eventoId)->presentes ?? 0);
            $avaliacoes = (int) ($presencas->get($eventoId)->avaliacoes ?? 0);

            $rows[] = [
                'evento_id' => $eventoId,
                'acao' => $prev->evento_nome,
                'previstas' => $prevQtd,
                'inscritos' => (int) ($inscritos[$eventoId] ?? 0),
                'presentes' => $presentes,
                'avaliacoes' => $avaliacoes,
                'pct_realizado' => $this->pct($presentes, $prevQtd),
            ];
        }

        usort($rows, fn ($a, $b) => strcmp((string) $a['acao'], (string) $b['acao']));

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $f
     */
    private function participacaoPorRegiao(array $f): array
    {
        $previstas = (clone $this->baseAtividades($f))
            ->selectRaw("COALESCE(regiaos.nome, 'Sem região') as regiao, SUM(atividades.publico_esperado) as previstas")
            ->groupBy('regiaos.nome')
            ->pluck('previstas', 'regiao');

        $presentes = (clone $this->basePresencas($f))
            ->selectRaw("COALESCE(regiaos.nome, 'Sem região') as regiao, COUNT(*) as presentes")
            ->groupBy('regiaos.nome')
            ->pluck('presentes', 'regiao');

        $regioes = $previstas->keys()->merge($presentes->keys())->unique()->sort()->values();

        return $regioes->map(function ($regiao) use ($previstas, $presentes) {
            $prev = (int) ($previstas[$regiao] ?? 0);
            $pres = (int) ($presentes[$regiao] ?? 0);

            return [
                'regiao' => $regiao,
                'previstas' => $prev,
                'presentes' => $pres,
                'pct_realizado' => $this->pct($pres, $prev),
            ];
        })->all();
    }

    /**
     * Comparação entre segmentos (tag do participante).
     *
     * @param  array<string, mixed>  $f
     */
    private function segmentos(array $f): array
    {
        $rows = (clone $this->basePresencas($f))
            ->selectRaw("COALESCE(NULLIF(participantes.tag, ''), 'Sem segmento') as segmento")
            ->selectRaw('COUNT(*) as presentes')
            ->selectRaw('COUNT(DISTINCT participantes.id) as participantes_unicos')
            ->groupBy('participantes.tag')
            ->orderByDesc('presentes')
            ->get();

        return $rows->map(fn ($r) => [
            'segmento' => $r->segmento,
            'presentes' => (int) $r->presentes,
            'participantes_unicos' => (int) $r->participantes_unicos,
        ])->all();
    }

    /**
     * Evolução semestral derivada da data da atividade (S1 = jan–jun).
     *
     * @param  array<string, mixed>  $f
     */
    private function evolucaoSemestral(array $f): array
    {
        $semestreExpr = "(EXTRACT(YEAR FROM atividades.dia)::int || '-S' || (CASE WHEN EXTRACT(MONTH FROM atividades.dia) <= 6 THEN 1 ELSE 2 END))";

        $eventos = (clone $this->baseAtividades($f))
            ->selectRaw("$semestreExpr as semestre, COUNT(DISTINCT atividades.evento_id) as eventos")
            ->whereNotNull('atividades.dia')
            ->groupByRaw($semestreExpr)
            ->pluck('eventos', 'semestre');

        $presencas = (clone $this->basePresencas($f))
            ->selectRaw("$semestreExpr as semestre")
            ->selectRaw('COUNT(*) as presentes')
            ->selectRaw('COUNT(CASE WHEN presencas.avaliacao_respondida = true THEN 1 END) as avaliacoes')
            ->whereNotNull('atividades.dia')
            ->groupByRaw($semestreExpr)
            ->get()
            ->keyBy('semestre');

        $semestres = $eventos->keys()->merge($presencas->keys())->unique()->sort()->values();

        return $semestres->map(fn ($s) => [
            'semestre' => $s,
            'eventos' => (int) ($eventos[$s] ?? 0),
            'presentes' => (int) ($presencas->get($s)->presentes ?? 0),
            'avaliacoes' => (int) ($presencas->get($s)->avaliacoes ?? 0),
        ])->all();
    }

    /**
     * Municípios cujo realizado (presentes/previstos) está abaixo do limite.
     *
     * @param  array<string, mixed>  $f
     */
    private function municipiosBaixoEngajamento(array $f): array
    {
        $limite = (float) config('painel-gerencial.engajamento_minimo_pct');

        $previstas = (clone $this->baseAtividades($f))
            ->selectRaw('atividades.municipio_id, municipios.nome as municipio_nome, regiaos.nome as regiao, SUM(atividades.publico_esperado) as previstas')
            ->whereNotNull('atividades.municipio_id')
            ->groupBy('atividades.municipio_id', 'municipios.nome', 'regiaos.nome')
            ->get()
            ->keyBy('municipio_id');

        $presentes = (clone $this->basePresencas($f))
            ->selectRaw('atividades.municipio_id, COUNT(*) as presentes')
            ->whereNotNull('atividades.municipio_id')
            ->groupBy('atividades.municipio_id')
            ->pluck('presentes', 'municipio_id');

        $rows = [];
        foreach ($previstas as $municipioId => $p) {
            $prev = (int) $p->previstas;
            $pres = (int) ($presentes[$municipioId] ?? 0);
            $pct = $this->pct($pres, $prev);

            if ($prev > 0 && $pct < $limite) {
                $rows[] = [
                    'municipio' => $p->municipio_nome,
                    'regiao' => $p->regiao ?? 'Sem região',
                    'previstas' => $prev,
                    'presentes' => $pres,
                    'pct_realizado' => $pct,
                ];
            }
        }

        usort($rows, fn ($a, $b) => $a['pct_realizado'] <=> $b['pct_realizado']);

        return $rows;
    }

    /**
     * Atividades já realizadas (data passada) sem nenhuma avaliação respondida.
     *
     * @param  array<string, mixed>  $f
     */
    private function eventosSemAvaliacao(array $f): array
    {
        $rows = (clone $this->baseAtividades($f))
            ->selectRaw('atividades.id, atividades.descricao, atividades.dia, eventos.nome as evento_nome, municipios.nome as municipio_nome')
            ->selectRaw('(SELECT COUNT(*) FROM presencas WHERE presencas.atividade_id = atividades.id AND presencas.status = \'presente\' AND presencas.avaliacao_respondida = true) as avaliacoes')
            ->whereDate('atividades.dia', '<', Carbon::today())
            ->get()
            ->filter(fn ($r) => (int) $r->avaliacoes === 0)
            ->values();

        return $rows->map(fn ($r) => [
            'acao' => $r->evento_nome,
            'momento' => $r->descricao,
            'municipio' => $r->municipio_nome,
            'dia' => $r->dia,
        ])->all();
    }

    /**
     * Participantes com N+ ausências (inscritos que não tiveram presença
     * 'presente' na atividade correspondente).
     *
     * @param  array<string, mixed>  $f
     */
    private function recorrenciaAusencia(array $f): array
    {
        $minimo = (int) config('painel-gerencial.recorrencia_ausencia_minima');

        $query = DB::table('inscricaos')
            ->join('atividades', 'inscricaos.atividade_id', '=', 'atividades.id')
            ->join('participantes', 'inscricaos.participante_id', '=', 'participantes.id')
            ->join('users', 'users.id', '=', 'participantes.user_id')
            ->leftJoin('municipios', 'municipios.id', '=', 'atividades.municipio_id')
            ->leftJoin('estados', 'estados.id', '=', 'municipios.estado_id')
            ->leftJoin('regiaos', 'regiaos.id', '=', 'estados.regiao_id')
            ->leftJoin('presencas', function ($join) {
                $join->on('presencas.inscricao_id', '=', 'inscricaos.id')
                    ->where('presencas.status', '=', 'presente');
            })
            ->whereNull('atividades.deleted_at')
            ->whereNotNull('atividades.evento_id')
            ->whereNull('presencas.id');

        $this->applyAtividadeFilters($query, $f, hasRegiaoJoin: true);

        $rows = $query
            ->selectRaw('participantes.id, users.name as nome, municipios.nome as municipio_nome, COUNT(*) as ausencias')
            ->groupBy('participantes.id', 'users.name', 'municipios.nome')
            ->havingRaw('COUNT(*) >= ?', [$minimo])
            ->orderByRaw('COUNT(*) DESC')
            ->get();

        return $rows->map(fn ($r) => [
            'participante' => $r->nome,
            'municipio' => $r->municipio_nome,
            'ausencias' => (int) $r->ausencias,
        ])->all();
    }

    private function pct(int $n, int $total): float
    {
        return $total > 0 ? round($n / $total * 100, 1) : 0.0;
    }

    /**
     * Resumo legível dos filtros aplicados (para cabeçalhos de PDF/XLSX).
     *
     * @param  array<string, mixed>  $f
     * @return array<string, string>
     */
    private function describeFilters(array $f): array
    {
        $resumo = [];

        if ($f['evento_id']) {
            $resumo['Ação'] = (string) DB::table('eventos')->where('id', $f['evento_id'])->value('nome');
        }
        if ($f['municipio_id']) {
            $resumo['Município'] = (string) DB::table('municipios')->where('id', $f['municipio_id'])->value('nome');
        }
        if ($f['regiao_id']) {
            $resumo['Região'] = (string) DB::table('regiaos')->where('id', $f['regiao_id'])->value('nome');
        }
        if ($f['de'] || $f['ate']) {
            $de = $f['de'] ? $f['de']->format('d/m/Y') : '...';
            $ate = $f['ate'] ? $f['ate']->format('d/m/Y') : '...';
            $resumo['Período'] = "$de a $ate";
        }
        if ($f['periodo']) {
            $resumo['Turno'] = ucfirst($f['periodo']);
        }

        return $resumo;
    }
}
