<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Atividade;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Participante;
use App\Models\User;
use App\Support\CargaHoraria;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AgendamentoEfetivacaoController extends Controller
{
    public function index()
    {
        $agendamentos = Agendamento::query()
            ->with(['atividadeAcao', 'municipio.estado', 'user'])
            ->withCount('participantesClonados')
            ->where('efetivado', false)
            ->orderBy('data_horario')
            ->paginate(15);

        return view('agendamentos.efetivacoes.index', compact('agendamentos'));
    }

    public function efetivados()
    {
        $agendamentos = Agendamento::query()
            ->with(['atividadeAcao', 'municipio.estado', 'user', 'atividade.evento'])
            ->withCount('participantesClonados')
            ->where('efetivado', true)
            ->orderByDesc('efetivado_em')
            ->orderByDesc('data_horario')
            ->paginate(15);

        return view('agendamentos.efetivacoes.efetivados', compact('agendamentos'));
    }

    public function create(Agendamento $agendamento)
    {
        $agendamento->load(['atividadeAcao', 'municipio.estado', 'user', 'atividade.evento'])
            ->loadCount('participantesClonados');

        $this->garantirAgendamentoPendente($agendamento);

        if (($agendamento->participantes_clonados_count ?? 0) < 1) {
            return redirect()
                ->route('agendamentos.participantes.index', $agendamento)
                ->withErrors(['participantes' => 'Cadastre pelo menos um participante antes de efetivar este agendamento.']);
        }

        $eventos = Evento::query()
            ->withCount('atividades')
            ->orderByDesc('data_inicio')
            ->orderBy('nome')
            ->get();

        $dadosPadrao = $this->dadosPadrao($agendamento);
        $resumo = $this->montarResumoParticipantes($agendamento);

        return view('agendamentos.efetivacoes.create', compact('agendamento', 'eventos', 'dadosPadrao', 'resumo'));
    }

    public function confirm(Request $request, Agendamento $agendamento)
    {
        $agendamento->load(['atividadeAcao', 'municipio.estado', 'user'])
            ->loadCount('participantesClonados');

        $this->garantirAgendamentoPendente($agendamento);

        $dados = $this->validarEfetivacao($request, $agendamento);
        $evento = Evento::query()->findOrFail($dados['evento_id']);
        $resumo = $this->montarResumoParticipantes($agendamento);

        return view('agendamentos.efetivacoes.confirm', compact('agendamento', 'evento', 'dados', 'resumo'));
    }

    public function store(Request $request, Agendamento $agendamento)
    {
        $agendamento->load(['atividadeAcao', 'municipio', 'user'])
            ->loadCount('participantesClonados');

        $this->garantirAgendamentoPendente($agendamento);

        $dados = $this->validarEfetivacao($request, $agendamento);
        $evento = Evento::query()->findOrFail($dados['evento_id']);

        $resultado = DB::transaction(function () use ($agendamento, $evento, $dados) {
            $atividade = $evento->atividades()->create([
                'descricao' => $dados['descricao'],
                'dia' => $dados['dia'],
                'hora_inicio' => $dados['hora_inicio'],
                'hora_fim' => $dados['hora_fim'],
                'publico_esperado' => $dados['publico_esperado'],
                'carga_horaria' => $dados['carga_horaria'],
                'municipio_id' => $agendamento->municipio_id,
                'presenca_ativa' => true,
                'checklist_planejamento' => [],
                'checklist_encerramento' => [],
            ]);

            $atividade->municipios()->sync([$agendamento->municipio_id]);

            $stats = $this->inscreverParticipantes($agendamento, $evento, $atividade);

            $agendamento->update([
                'efetivado' => true,
                'efetivado_em' => now(),
                'atividade_id' => $atividade->id,
            ]);

            return [$atividade, $stats];
        });

        [$atividade, $stats] = $resultado;

        $mensagem = sprintf(
            'Agendamento efetivado com sucesso. Momento criado e %d participante(s) inscrito(s).',
            $stats['inscricoes']
        );

        if ($stats['usuarios_criados'] > 0) {
            $mensagem .= ' Usuários criados automaticamente: '.$stats['usuarios_criados'].'.';
        }

        if ($stats['inscricoes_restauradas'] > 0) {
            $mensagem .= ' Inscrições reativadas: '.$stats['inscricoes_restauradas'].'.';
        }

        return redirect()
            ->route('atividades.show', $atividade)
            ->with('success', $mensagem);
    }

    private function validarEfetivacao(Request $request, Agendamento $agendamento): array
    {
        $dados = $request->validate([
            'evento_id' => ['required', 'integer', 'exists:eventos,id'],
            'descricao' => ['required', 'string', 'max:2000'],
            'dia' => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'publico_esperado' => ['nullable', 'integer', 'min:0'],
            'carga_horas' => ['nullable', 'integer', 'min:0'],
            'carga_minutos' => ['nullable', 'integer', 'min:0', 'max:59'],
        ]);

        if (($agendamento->participantes_clonados_count ?? $agendamento->participantesClonados()->count()) < 1) {
            throw ValidationException::withMessages([
                'evento_id' => 'Cadastre pelo menos um participante antes de efetivar este agendamento.',
            ]);
        }

        $dados['publico_esperado'] = $dados['publico_esperado'] ?? $agendamento->participantesClonados()->count();

        $cargaHoras = (int) ($dados['carga_horas'] ?? 0);
        $cargaMinutos = (int) ($dados['carga_minutos'] ?? 0);
        unset($dados['carga_horas'], $dados['carga_minutos']);

        $manual = CargaHoraria::totalMinutosFromPartes($cargaHoras, $cargaMinutos);
        $dados['carga_horaria'] = $manual ?? $this->calcularCargaHoraria($dados['hora_inicio'], $dados['hora_fim']);
        $dados['carga_horas'] = $cargaHoras;
        $dados['carga_minutos'] = $cargaMinutos;

        return $dados;
    }

    private function dadosPadrao(Agendamento $agendamento): array
    {
        $inicio = $agendamento->data_horario?->copy() ?? now();
        $fim = $inicio->copy()->addHour();

        return [
            'descricao' => $this->descricaoSugerida($agendamento),
            'dia' => $inicio->format('Y-m-d'),
            'hora_inicio' => $inicio->format('H:i'),
            'hora_fim' => $fim->format('H:i'),
            'publico_esperado' => $agendamento->participantes_clonados_count ?? 0,
            'carga_horas' => 1,
            'carga_minutos' => 0,
        ];
    }

    private function descricaoSugerida(Agendamento $agendamento): string
    {
        $partes = [
            trim((string) ($agendamento->atividadeAcao?->nome ?? 'Momento')),
        ];

        if ($agendamento->turma) {
            $partes[] = 'Turma '.trim((string) $agendamento->turma);
        }

        if ($agendamento->publico_participante) {
            $partes[] = 'Público: '.trim((string) $agendamento->publico_participante);
        }

        if ($agendamento->local_acao) {
            $partes[] = 'Local: '.trim((string) $agendamento->local_acao);
        }

        return implode(' | ', array_filter($partes));
    }

    private function calcularCargaHoraria(string $horaInicio, string $horaFim): int
    {
        $inicio = Carbon::createFromFormat('H:i', $horaInicio);
        $fim = Carbon::createFromFormat('H:i', $horaFim);

        return max(1, (int) $inicio->diffInMinutes($fim));
    }

    private function montarResumoParticipantes(Agendamento $agendamento): array
    {
        $participantes = $agendamento->participantesClonados()->get();

        $encontrados = 0;
        $seraoCriados = 0;

        foreach ($participantes as $participante) {
            if ($this->localizarParticipanteExistente($participante)) {
                $encontrados++;

                continue;
            }

            $seraoCriados++;
        }

        return [
            'total' => $participantes->count(),
            'com_email' => $participantes->filter(fn ($p) => filled($p->email))->count(),
            'sem_email' => $participantes->filter(fn ($p) => blank($p->email))->count(),
            'com_cpf' => $participantes->filter(fn ($p) => filled($p->cpf))->count(),
            'usuarios_existentes' => $encontrados,
            'usuarios_a_criar' => $seraoCriados,
        ];
    }

    private function inscreverParticipantes(Agendamento $agendamento, Evento $evento, Atividade $atividade): array
    {
        $participantesAgendados = $agendamento->participantesClonados()->orderBy('id')->get();

        $stats = [
            'usuarios_criados' => 0,
            'inscricoes' => 0,
            'inscricoes_restauradas' => 0,
        ];

        foreach ($participantesAgendados as $participanteAgendado) {
            $participante = $this->resolverParticipante($agendamento, $participanteAgendado, $stats);

            $inscricao = Inscricao::withTrashed()
                ->where('evento_id', $evento->id)
                ->where('atividade_id', $atividade->id)
                ->where('participante_id', $participante->id)
                ->first();

            if ($inscricao && $inscricao->deleted_at === null) {
                $stats['inscricoes']++;

                continue;
            }

            if (! $inscricao) {
                $inscricao = Inscricao::withTrashed()
                    ->where('evento_id', $evento->id)
                    ->whereNull('atividade_id')
                    ->where('participante_id', $participante->id)
                    ->first();
            }

            if ($inscricao) {
                $stats['inscricoes_restauradas']++;
                $inscricao->fill([
                    'evento_id' => $evento->id,
                    'atividade_id' => $atividade->id,
                    'participante_id' => $participante->id,
                    'ouvinte' => false,
                ]);
                $inscricao->deleted_at = null;
                $inscricao->save();
            } else {
                Inscricao::create([
                    'evento_id' => $evento->id,
                    'atividade_id' => $atividade->id,
                    'participante_id' => $participante->id,
                    'ouvinte' => false,
                ]);
            }

            $stats['inscricoes']++;
        }

        return $stats;
    }

    private function resolverParticipante(
        Agendamento $agendamento,
        $participanteAgendado,
        array &$stats
    ): Participante {
        $participanteExistente = $this->localizarParticipanteExistente($participanteAgendado);
        if ($participanteExistente) {
            return $this->atualizarParticipanteExistente(
                $participanteExistente,
                $participanteExistente->user,
                $agendamento,
                $participanteAgendado,
                $this->normalizarCpf($participanteAgendado->cpf)
            );
        }

        $user = User::create([
            'name' => trim((string) $participanteAgendado->nome) ?: 'Participante do agendamento',
            'email' => $this->resolverEmailParaNovoUsuario($agendamento, $participanteAgendado),
            'password' => Hash::make($this->senhaPadraoNovoUsuario()),
        ]);
        $user->assignRole('participante');

        $participante = $this->atualizarParticipanteExistente(
            $user->participante,
            $user,
            $agendamento,
            $participanteAgendado,
            $this->normalizarCpf($participanteAgendado->cpf)
        );

        $stats['usuarios_criados']++;

        return $participante;
    }

    private function localizarParticipanteExistente($participanteAgendado): ?Participante
    {
        $email = strtolower(trim((string) ($participanteAgendado->email ?? '')));
        $cpf = $this->normalizarCpf($participanteAgendado->cpf);

        $participantePorCpf = null;
        if ($cpf !== null) {
            $participantePorCpf = Participante::query()
                ->with('user')
                ->where('cpf', $cpf)
                ->first();
        }

        $userPorEmail = null;
        if ($email !== '') {
            $userPorEmail = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
        }

        if ($participantePorCpf) {
            return $participantePorCpf;
        }

        if (! $userPorEmail) {
            return null;
        }

        return $userPorEmail->participante ?: $userPorEmail->participante()->create();
    }

    private function atualizarParticipanteExistente(
        ?Participante $participante,
        ?User $user,
        Agendamento $agendamento,
        $participanteAgendado,
        string $cpf
    ): Participante {
        if (! $participante) {
            $participante = Participante::create([
                'user_id' => $user?->id,
            ]);
        }

        $dados = [
            'municipio_id' => $participante->municipio_id ?: $agendamento->municipio_id,
            'cpf' => $participante->cpf ?: ($cpf !== '' ? $cpf : null),
            'telefone' => $participante->telefone ?: $this->normalizarTelefone($participanteAgendado->telefone),
            'tag' => $participante->tag ?: $this->normalizarTexto($participanteAgendado->vinculo),
            'tipo_organizacao' => $participante->tipo_organizacao ?: $this->normalizarTexto($participanteAgendado->origem),
        ];

        $participante->fill($dados);
        $participante->save();

        return $participante;
    }

    private function gerarEmailPlaceholder(Agendamento $agendamento, $participanteAgendado): string
    {
        $base = 'agendamento-'.$agendamento->id.'-participante-'.$participanteAgendado->id;
        $email = $base.'@engaja.local';
        $suffix = 1;

        while (User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)])->exists()) {
            $email = $base.'-'.$suffix.'@engaja.local';
            $suffix++;
        }

        return $email;
    }

    private function resolverEmailParaNovoUsuario(Agendamento $agendamento, $participanteAgendado): string
    {
        $emailInformado = strtolower(trim((string) ($participanteAgendado->email ?? '')));

        if ($emailInformado !== '' && ! User::query()->whereRaw('LOWER(email) = ?', [$emailInformado])->exists()) {
            return $emailInformado;
        }

        return $this->gerarEmailPlaceholder($agendamento, $participanteAgendado);
    }

    private function senhaPadraoNovoUsuario(): string
    {
        return 'A'.now()->format('dmY').'b#';
    }

    private function normalizarTelefone(?string $telefone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $telefone);

        return $digits !== '' ? $digits : null;
    }

    private function normalizarCpf(?string $cpf): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $cpf);

        return $digits !== '' ? $digits : null;
    }

    private function normalizarTexto(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor !== '' ? Str::limit($valor, 100, '') : null;
    }

    private function garantirAgendamentoPendente(Agendamento $agendamento): void
    {
        abort_if($agendamento->efetivado, 404);
    }
}
