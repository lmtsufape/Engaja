<?php

namespace App\Http\Controllers;

use App\Mail\AgendamentoCriadoMail;
use App\Models\Agendamento;
use App\Models\AtividadeAcao;
use App\Models\Municipio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AgendamentoController extends Controller
{
    public function index()
    {
        $agendamentos = Agendamento::query()
            ->with(['atividadeAcao', 'municipio.estado', 'user', 'atividade.evento'])
            ->withCount('participantesClonados')
            ->where('user_id', auth()->id())
            ->orderBy('data_horario')
            ->get();

        return view('agendamentos.index', compact('agendamentos'));
    }

    public function create()
    {
        $atividadeAcoes = AtividadeAcao::query()
            ->orderBy('nome')
            ->get();

        $user = auth()->user();
        $permiteEscolherMunicipio = $this->usuarioPodeEscolherMunicipio($user);
        $municipio = $permiteEscolherMunicipio ? null : $this->municipioDoUsuario($user);
        $municipios = $this->municipiosParaSelecao($permiteEscolherMunicipio);

        return view('agendamentos.create', compact('atividadeAcoes', 'municipio', 'municipios', 'permiteEscolherMunicipio'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $permiteEscolherMunicipio = $this->usuarioPodeEscolherMunicipio($user);
        $dados = $this->validarDados($request, $permiteEscolherMunicipio);
        $dados = $this->aplicarMunicipio($dados, $user, $permiteEscolherMunicipio);
        $dados['user_id'] = auth()->id();

        $agendamento = Agendamento::create($dados);
        $agendamento->load('atividadeAcao', 'municipio');

        User::permission('agendamento.notificar')
            ->whereNotNull('email')
            ->each(fn (User $user) => Mail::to($user->email)->queue(new AgendamentoCriadoMail($agendamento)));

        return redirect()
            ->route('agendamentos.index')
            ->with('success', 'Agendamento criado com sucesso.');
    }

    public function show(Agendamento $agendamento)
    {
        $agendamento->load(['atividadeAcao', 'municipio.estado', 'user', 'atividade.evento'])
            ->loadCount('participantesClonados');

        return view('agendamentos.show', compact('agendamento'));
    }

    public function edit(Agendamento $agendamento)
    {
        $this->garantirNaoEfetivado($agendamento);

        $atividadeAcoes = AtividadeAcao::query()
            ->orderBy('nome')
            ->get();

        $user = auth()->user();
        $permiteEscolherMunicipio = $this->usuarioPodeEscolherMunicipio($user);
        $municipio = $permiteEscolherMunicipio ? null : $this->municipioDoUsuario($user);
        $municipios = $this->municipiosParaSelecao($permiteEscolherMunicipio);

        return view('agendamentos.edit', compact('agendamento', 'atividadeAcoes', 'municipio', 'municipios', 'permiteEscolherMunicipio'));
    }

    public function update(Request $request, Agendamento $agendamento)
    {
        $this->garantirNaoEfetivado($agendamento);

        $user = auth()->user();
        $permiteEscolherMunicipio = $this->usuarioPodeEscolherMunicipio($user);
        $dados = $this->validarDados($request, $permiteEscolherMunicipio);
        $dados = $this->aplicarMunicipio($dados, $user, $permiteEscolherMunicipio);
        $dados['user_id'] = auth()->id();

        $agendamento->update($dados);

        return redirect()
            ->route('agendamentos.index')
            ->with('success', 'Agendamento atualizado com sucesso.');
    }

    public function destroy(Agendamento $agendamento)
    {
        $this->garantirNaoEfetivado($agendamento);

        $agendamento->delete();

        return redirect()
            ->route('agendamentos.index')
            ->with('success', 'Agendamento removido com sucesso.');
    }

    private function validarDados(Request $request, bool $validarMunicipio = false): array
    {
        $rules = [
            'data_horario' => 'required|date',
            'atividade_acao_id' => 'required|exists:atividade_acoes,id',
            'publico_participante' => 'required|string|max:255',
            'local_acao' => 'required|string|max:255',
            'turma' => 'nullable|string|max:255',
        ];

        if ($validarMunicipio) {
            $rules['municipio_id'] = 'required|exists:municipios,id';
        }

        $dados = $request->validate($rules, [
            'municipio_id.required' => 'Selecione o município do agendamento.',
            'municipio_id.exists' => 'O município selecionado não é válido.',
        ]);

        $atividadeAcao = AtividadeAcao::query()->findOrFail($dados['atividade_acao_id']);

        if (! $atividadeAcao->usa_turmas) {
            $dados['turma'] = null;

            return $dados;
        }

        $turmasDisponiveis = $atividadeAcao->turmas_configuradas;
        $turmaSelecionada = trim((string) ($dados['turma'] ?? ''));

        if ($turmaSelecionada === '') {
            throw ValidationException::withMessages([
                'turma' => 'Selecione a turma para este agendamento.',
            ]);
        }

        if (! in_array($turmaSelecionada, $turmasDisponiveis, true)) {
            throw ValidationException::withMessages([
                'turma' => 'A turma selecionada não é válida para a atividade/ação escolhida.',
            ]);
        }

        $dados['turma'] = $turmaSelecionada;

        return $dados;
    }

    private function municipioDoUsuario(?User $user): ?Municipio
    {
        if (! $user) {
            return null;
        }

        return $user->participante()
            ->with('municipio.estado')
            ->first()
            ?->municipio;
    }

    private function usuarioPodeEscolherMunicipio(?User $user): bool
    {
        return $user && ! $user->hasRole('SME');
    }

    private function municipiosParaSelecao(bool $incluir): Collection
    {
        if (! $incluir) {
            return collect();
        }

        return Municipio::query()
            ->with('estado:id,nome,sigla')
            ->orderBy('nome')
            ->get(['id', 'nome', 'estado_id']);
    }

    private function resolverMunicipioId(array $dados, ?User $user, bool $permitirEscolha): ?int
    {
        if ($permitirEscolha) {
            return (int) ($dados['municipio_id'] ?? 0) ?: null;
        }

        return $this->municipioDoUsuario($user)?->id;
    }

    private function aplicarMunicipio(array $dados, ?User $user, bool $permitirEscolha): array
    {
        $municipioId = $this->resolverMunicipioId($dados, $user, $permitirEscolha);
        if (! $municipioId) {
            throw ValidationException::withMessages([
                'municipio_id' => 'Seu participante não possui município preenchido. Atualize seu perfil para cadastrar agendamentos.',
            ]);
        }

        $dados['municipio_id'] = $municipioId;

        return $dados;
    }

    private function garantirNaoEfetivado(Agendamento $agendamento): void
    {
        if (! $agendamento->efetivado) {
            return;
        }

        abort(403, 'Agendamentos efetivados não podem mais ser alterados ou excluídos.');
    }
}
