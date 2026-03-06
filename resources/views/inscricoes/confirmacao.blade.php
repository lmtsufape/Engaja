@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Confirmacao da Importacao - {{ $evento->nome }}</h1>

  <div class="text-muted mb-2">
    Momento selecionado:
    <strong>{{ $atividade->descricao ?: 'Momento' }}</strong>
    -
    {{ \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') }}
    @if($atividade->hora_inicio)
      as {{ \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i') }}
    @endif
  </div>

  <div class="text-muted mb-3">
    Na importacao:
    <strong>{{ $usuariosNovosCount }}</strong> novo(s) cadastro(s)
    |
    <strong>{{ $usuariosExistentesCount }}</strong> usuario(s) ja existente(s) (dados serao atualizados).
  </div>

  @if ($errors->any())
  <div class="alert alert-danger">
    <strong>Corrija os erros antes de confirmar:</strong>
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="text-muted">
      {{ $rows->total() }} novo(s) usuario(s) identificado(s) |
      Pagina {{ $rows->currentPage() }} de {{ $rows->lastPage() }} |
      exibindo {{ $rows->count() }} por pagina
    </div>

    <div class="d-flex gap-2">
      <a
        href="{{ route('inscricoes.preview', ['evento' => $evento, 'session_key' => $sessionKey, 'atividade_id' => $atividade->id]) }}"
        class="btn btn-outline-secondary">
        Voltar
      </a>

      <form method="POST" action="{{ route('inscricoes.confirmar', $evento) }}">
        @csrf
        <input type="hidden" name="session_key" value="{{ $sessionKey }}">
        <input type="hidden" name="atividade_id" value="{{ $atividade->id }}">
        <button class="btn btn-primary">Confirmar e salvar (todas as paginas)</button>
      </form>
    </div>
  </div>

  @php
    $municipiosPorId = $municipios->keyBy('id');
  @endphp

  <div class="table-responsive">
    <table class="table table-sm align-middle table-bordered bg-white">
      <thead class="table-light">
        <tr>
          <th style="min-width:220px;">Nome</th>
          <th style="min-width:240px;">Email</th>
          <th style="min-width:140px;">CPF</th>
          <th style="min-width:140px;">Telefone</th>
          <th style="min-width:260px;">Municipio</th>
          <th style="min-width:220px;">Tipo de Organizacao</th>
          <th style="min-width:220px;">Organizacao</th>
          <th style="min-width:200px;">Tag</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $r)
          @php
            $municipio = null;
            if (!empty($r['municipio_id'])) {
              $municipio = $municipiosPorId->get((int) $r['municipio_id']);
            }
          @endphp
          <tr>
            <td>{{ $r['nome'] ?? '-' }}</td>
            <td>{{ $r['email'] ?? '-' }}</td>
            <td>{{ $r['cpf'] ?? '-' }}</td>
            <td>{{ $r['telefone'] ?? '-' }}</td>
            <td>{{ $municipio?->nome_com_estado ?? '-' }}</td>
            <td>{{ $r['tipo_organizacao'] ?? ($r['organizacao'] ?? '-') }}</td>
            <td>{{ $r['escola_unidade'] ?? ($r['organizacao_nome'] ?? '-') }}</td>
            <td>{{ $r['tag'] ?? '-' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-muted">Nenhum novo usuario para cadastro nesta importacao.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="d-flex align-items-center justify-content-between">
    <div>
      {{ $rows->appends(['session_key' => $sessionKey, 'per_page' => $rows->perPage(), 'atividade_id' => $atividade->id])->links() }}
    </div>
  </div>
</div>
@endsection
