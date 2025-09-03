{{-- resources/views/inscricoes/preview.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Pré-visualização da Importação — {{ $evento->nome }}</h1>

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
      {{ $rows->total() }} linha(s) no total •
      Página {{ $rows->currentPage() }} de {{ $rows->lastPage() }} •
      exibindo {{ $rows->count() }} por página
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('inscricoes.import', $evento) }}" class="btn btn-outline-secondary">Voltar</a>

      {{-- Confirmar TUDO (todas as páginas/sessão) --}}
      <form method="POST" action="{{ route('inscricoes.confirmar', $evento) }}">
        @csrf
        <input type="hidden" name="session_key" value="{{ $sessionKey }}">
        <button class="btn btn-primary">Confirmar e salvar (todas as páginas)</button>
      </form>
    </div>
  </div>

  {{-- Salvar alterações da página atual na sessão --}}
  <form id="form-save-page" method="POST"
        action="{{ route('inscricoes.preview.save', [
            'evento' => $evento,
            'page' => $rows->currentPage(),
            'per_page' => $rows->perPage()
        ]) }}">
    @csrf
    <input type="hidden" name="session_key" value="{{ $sessionKey }}">

    <div class="table-responsive">
      <table class="table table-sm align-middle table-bordered bg-white">
        <thead class="table-light">
          <tr>
            <th style="min-width:220px;">Nome *</th>
            <th style="min-width:240px;">Email *</th>
            <th style="min-width:140px;">CPF</th>
            <th style="min-width:140px;">Telefone</th>
            <th style="min-width:260px;">Município</th>
            <th style="min-width:220px;">Escola/Unidade</th>
            <!-- <th style="min-width:140px;">Data de entrada</th> -->
          </tr>
        </thead>
        <tbody id="preview-tbody">
          @foreach($rows as $i => $r)
            @php $idx = $globalOffset + $loop->index; @endphp
            <tr>
              <td><input class="form-control form-control-sm" name="rows[{{ $idx }}][nome]" value="{{ old("rows.$idx.nome", $r['nome']) }}" required></td>
              <td><input type="email" class="form-control form-control-sm" name="rows[{{ $idx }}][email]" value="{{ old("rows.$idx.email", $r['email']) }}" required></td>
              <td><input class="form-control form-control-sm" name="rows[{{ $idx }}][cpf]" value="{{ old("rows.$idx.cpf", $r['cpf']) }}"></td>
              <td><input class="form-control form-control-sm" name="rows[{{ $idx }}][telefone]" value="{{ old("rows.$idx.telefone", $r['telefone']) }}"></td>

              <td>
                <select class="form-select form-select-sm" name="rows[{{ $idx }}][municipio_id]">
                  <option value="">-- Nenhum --</option>
                  @foreach($municipios as $m)
                    <option value="{{ $m->id }}" @selected(old("rows.$idx.municipio_id", $r['municipio_id']) == $m->id)>
                      {{ $m->nome_com_estado }}
                    </option>
                  @endforeach
                </select>
              </td>

              <td><input class="form-control form-control-sm" name="rows[{{ $idx }}][escola_unidade]" value="{{ old("rows.$idx.escola_unidade", $r['escola_unidade']) }}"></td>
              <!-- <td><input class="form-control form-control-sm" name="rows[{{ $idx }}][data_entrada]" value="{{ old("rows.$idx.data_entrada", $r['data_entrada']) }}" placeholder="YYYY-MM-DD"></td> -->
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="d-flex align-items-center justify-content-between">
      <div>
        {{-- mantém session_key e per_page definidos pelo controller --}}
        {{ $rows->appends(['session_key' => $sessionKey, 'per_page' => $rows->perPage()])->links() }}
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm">Salvar alterações desta página</button>
      </div>
    </div>
  </form>
</div>
@endsection
