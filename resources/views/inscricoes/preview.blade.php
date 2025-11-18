{{-- resources/views/inscricoes/preview.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Pré-visualização da Importação - {{ $evento->nome }}</h1>
  <div class="text-muted mb-3">
    Momento selecionado:
    <strong>{{ $atividade->descricao ?: 'Momento' }}</strong>
    —
    {{ \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') }}
    @if($atividade->hora_inicio)
      às {{ \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i') }}
    @endif
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
        <input type="hidden" name="atividade_id" value="{{ $atividade->id }}">
        <button class="btn btn-primary">Confirmar e salvar (todas as páginas)</button>
      </form>
    </div>
  </div>

  {{-- Salvar alterações da página atual na sessão --}}
  <form id="form-save-page" method="POST"
    action="{{ route('inscricoes.preview.save', [
            'evento' => $evento,
            'page' => $rows->currentPage(),
            'per_page' => $rows->perPage(),
            'atividade_id' => $atividade->id,
        ]) }}">
    @csrf
    <input type="hidden" name="session_key" value="{{ $sessionKey }}">
    <input type="hidden" name="atividade_id" value="{{ $atividade->id }}">

    <div class="table-responsive">
      @php $tagOptions = $participanteTags ?? config('engaja.participante_tags', \App\Models\Participante::TAGS); @endphp
      <table class="table table-sm align-middle table-bordered bg-white">
        <thead class="table-light">
          <tr>
            <th style="min-width:220px;">Nome *</th>
            <th style="min-width:240px;">Email *</th>
            <th style="min-width:140px;">CPF</th>
            <th style="min-width:140px;">Telefone</th>
            <th style="min-width:260px;">Município</th>
            <th style="min-width:220px;">Tipo de Organização</th>
            <th style="min-width:220px;">Organização</th>
            <th style="min-width:200px;">Tag</th>
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
                <option value="{{ $m->id }}" @selected(old("rows.$idx.municipio_id", $r['municipio_id'])==$m->id)>
                  {{ $m->nome_com_estado }}
                </option>
                @endforeach
              </select>
            </td>

            <td>
              @php
              $valorTipo = $r['tipo_organizacao'] ?? null;
              @endphp

              <select
                name="rows[{{ $globalOffset + $loop->index }}][tipo_organizacao]"
                class="form-select form-select-sm {{ (!empty($r['tipo_organizacao']) && empty($r['tipo_organizacao_ok'])) ? 'is-invalid' : '' }}">
                <option value="">Selecione...</option>
                @foreach($organizacoes as $org)
                <option value="{{ $org }}" @selected($valorTipo===$org)>{{ $org }}</option>
                @endforeach
              </select>

              @if(!empty($r['tipo_organizacao']) && empty($r['tipo_organizacao_ok']))
              <div class="invalid-feedback">Selecione um tipo de organização válido.</div>
              @endif
            </td>
            <td>
              <input
                class="form-control form-control-sm"
                name="rows[{{ $idx }}][escola_unidade]"
                value="{{ old("rows.$idx.escola_unidade", $r['escola_unidade'] ?? '') }}">
            </td>


            <td>
              <select
                name="rows[{{ $idx }}][tag]"
                class="form-select form-select-sm {{ (!empty($r['tag']) && empty($r['tag_ok'])) ? 'is-invalid' : '' }}">
                <option value="">Selecione...</option>
                @foreach($tagOptions as $tagOption)
                <option value="{{ $tagOption }}" @selected(old("rows.$idx.tag", $r['tag']) === $tagOption)>{{ $tagOption }}</option>
                @endforeach
              </select>

              @if(!empty($r['tag']) && empty($r['tag_ok']))
              <div class="invalid-feedback">Selecione uma tag válida.</div>
              @endif
            </td>

            <!-- <td><input class="form-control form-control-sm" name="rows[{{ $idx }}][data_entrada]" value="{{ old("rows.$idx.data_entrada", $r['data_entrada']) }}" placeholder="YYYY-MM-DD"></td> -->
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="d-flex align-items-center justify-content-between">
      <div>
        {{-- mantém session_key e per_page definidos pelo controller --}}
        {{ $rows->appends(['session_key' => $sessionKey, 'per_page' => $rows->perPage(), 'atividade_id' => $atividade->id])->links() }}
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm">Salvar alterações desta página</button>
      </div>
    </div>
  </form>
</div>
@endsection
