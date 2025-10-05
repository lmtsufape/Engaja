@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h1 class="h5 mb-3">
    Pré-visualização de presenças — {{ $evento->nome }}<br>
    <small class="text-muted">
      Momento: {{ \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') }}
      • {{ \Illuminate\Support\Str::of($atividade->hora_inicio)->substr(0,5) }}
      • {{ $atividade->descricao ?? 'Momento' }}
    </small>
  </h1>

  <form method="POST" action="{{ route('atividades.presencas.savepage', $atividade) }}" class="mb-3">
    @csrf
    <input type="hidden" name="session_key" value="{{ $sessionKey }}">

    <div class="table-responsive">
      @php $tagOptions = $participanteTags ?? config('engaja.participante_tags', \App\Models\Participante::TAGS); @endphp
      <table class="table table-sm table-bordered align-middle bg-white">
        <thead class="table-light">
          <tr>
            <th>Nome</th>
            <th>Email</th>
            <th>CPF</th>
            <th>Telefone</th>
            <th>Município</th>
            <th>Organização</th>
            <th>Tag</th>
            <th style="min-width:150px;">Status</th>
            <!-- <th>Justificativa</th> -->
            <!-- <th>Data entrada</th> -->
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $i => $r)
          @php $gi = $globalOffset + $i; @endphp
          <tr>
            <td><input name="rows[{{ $gi }}][nome]" class="form-control form-control-sm" value="{{ $r['nome'] }}"></td>
            <td><input name="rows[{{ $gi }}][email]" class="form-control form-control-sm" value="{{ $r['email'] }}"></td>
            <td><input name="rows[{{ $gi }}][cpf]" class="form-control form-control-sm" value="{{ $r['cpf'] }}"></td>
            <td><input name="rows[{{ $gi }}][telefone]" class="form-control form-control-sm" value="{{ $r['telefone'] }}"></td>
            <td>
              <select name="rows[{{ $gi }}][municipio_id]"
                class="form-select form-select-sm">
                <option value="">— Selecione —</option>
                @foreach($municipios as $m)
                <option value="{{ $m->id }}"
                  @selected((string)($r['municipio_id'] ?? '' )===(string)$m->id)>
                  {{ $m->nome_com_estado }}
                </option>
                @endforeach
              </select>
            </td>
            <td>
              <select
                name="rows[{{ $globalOffset + $loop->index }}][organizacao]"
                class="form-select form-select-sm {{ (!empty($r['organizacao']) && empty($r['organizacao_ok'])) ? 'is-invalid' : '' }}">
                <option value="">Selecione...</option>
                @foreach($organizacoes as $org)
                <option value="{{ $org }}" @selected(($r['organizacao'] ?? '' )===$org)>{{ $org }}</option>
                @endforeach
              </select>

              @if(!empty($r['organizacao']) && empty($r['organizacao_ok']))
              <div class="invalid-feedback">
                Valor importado não está na lista. Selecione uma organização válida.
              </div>
              @endif
            </td>
            <td>
              <select
                name="rows[{{ $gi }}][tag]"
                class="form-select form-select-sm {{ (!empty($r['tag']) && empty($r['tag_ok'])) ? 'is-invalid' : '' }}">
                <option value="">Selecione...</option>
                @foreach($tagOptions as $tagOption)
                <option value="{{ $tagOption }}" @selected(($r['tag'] ?? '')===$tagOption)>{{ $tagOption }}</option>
                @endforeach
              </select>

              @if(!empty($r['tag']) && empty($r['tag_ok']))
              <div class="invalid-feedback">Selecione uma tag válida.</div>
              @endif
            </td>
            <td>
              <select name="rows[{{ $gi }}][status]" class="form-select form-select-sm">
                <option value="">— Selecionar —</option>
                <option value="presente" @selected($r['status']==='presente' )>Presente</option>
                <option value="ausente" @selected($r['status']==='ausente' )>Ausente</option>
                <option value="justificado" @selected($r['status']==='justificado' )>Justificado</option>
              </select>
            </td>
            <!-- <td><input name="rows[{{ $gi }}][justificativa]" class="form-control form-control-sm" value="{{ $r['justificativa'] }}"></td> -->
            <!-- <td><input type="date" name="rows[{{ $gi }}][data_entrada]" class="form-control form-control-sm" value="{{ $r['data_entrada'] }}"></td> -->
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
      <div>{{ $rows->links() }}</div>
      <div class="d-flex gap-2">
        <a href="{{ route('atividades.presencas.preview', ['atividade'=>$atividade, 'session_key'=>$sessionKey]) }}" class="btn btn-outline-secondary btn-sm">Recarregar</a>
        <button class="btn btn-primary btn-sm">Salvar esta página</button>
      </div>
    </div>
  </form>

  <form method="POST" action="{{ route('atividades.presencas.confirmar', $atividade) }}" class="mt-3">
    @csrf
    <input type="hidden" name="session_key" value="{{ $sessionKey }}">
    <button class="btn btn-engaja">Confirmar e salvar tudo</button>
  </form>
</div>
@endsection