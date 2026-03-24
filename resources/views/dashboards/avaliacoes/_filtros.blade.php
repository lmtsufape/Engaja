<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-lg-3 col-md-6">
        <label class="form-label text-muted small mb-1">Modelo</label>
        <select class="form-select js-filter" id="f-template">
          <option value="">Todos</option>
          @foreach($templates as $template)
          <option value="{{ $template->id }}" @selected(request('template_id') == $template->id)>{{ $template->nome }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-3 col-md-6">
        <label class="form-label text-muted small mb-1">Evento</label>
        <select class="form-select js-filter" id="f-evento">
          <option value="">Todos</option>
          @foreach($eventos as $evento)
          <option value="{{ $evento->id }}">{{ $evento->nome }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-3 col-md-6">
        <label class="form-label text-muted small mb-1">Atividade / momento</label>
        <select class="form-select js-filter" id="f-atividade">
          <option value="">Todas</option>
          @foreach($atividades as $atividade)
          @php
            $diaFormatado = $atividade->dia ? \Illuminate\Support\Carbon::parse($atividade->dia)->format('d/m') : '';
          @endphp
          <option value="{{ $atividade->id }}">
            {{ $atividade->descricao ?? 'Momento' }} - {{ $diaFormatado }} {{ $atividade->hora_inicio }}
            @if($atividade->evento) ({{ $atividade->evento->nome }}) @endif
          </option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label text-muted small mb-1">De</label>
            <input type="date" class="form-control js-filter" id="f-de">
          </div>
          <div class="col-6">
            <label class="form-label text-muted small mb-1">Ate</label>
            <input type="date" class="form-control js-filter" id="f-ate">
          </div>
        </div>
      </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3">
      <button class="btn btn-primary" id="btn-recarregar">
        Atualizar agora
      </button>
    </div>
  </div>
</div>
