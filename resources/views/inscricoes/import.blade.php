@extends('layouts.app')

@section('content')
<div class="container">
  {{-- Cabeçalho --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h4 fw-bold text-engaja mb-1">Importar inscrições</h1>
      <div class="text-muted small">
        Ação pedagógica: <strong>{{ $evento->nome }}</strong>
        @if($evento->data_horario)
          • {{ \Carbon\Carbon::parse($evento->data_horario)->format('d/m/Y H:i') }}
        @endif
      </div>
    </div>

    <a href="{{ route('eventos.show', $evento) }}" class="btn btn-outline-secondary">
      Voltar à ação pedagógica
    </a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger">
      <strong>Ops!</strong> Verifique o arquivo e tente novamente.
    </div>
  @endif

  {{-- Card do Formulário --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <form method="POST"
            action="{{ route('inscricoes.cadastro', $evento) }}"
            enctype="multipart/form-data"
            class="row g-3">
        @csrf

        <div class="col-12">
          <label class="form-label">Arquivo Excel (.xlsx) <span class="text-danger">*</span></label>
          <input type="file"
                 name="your_file"
                 class="form-control @error('your_file') is-invalid @enderror"
                 accept=".xlsx,.xls"
                 required>
          @error('your_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
          <div class="form-text">
            Envie um arquivo Excel com a primeira linha como cabeçalho.
          </div>
        </div>

        {{-- (Opcional) Pré-visualização das colunas esperadas --}}
        <div class="col-12">
          <div class="ev-card p-3">
            <div class="fw-semibold mb-2">Formato sugerido (colunas):</div>
            <div class="small text-muted">
              <code>nome</code>, <code>email</code>, <code>cpf</code>, <code>telefone</code>, <code>municipio</code>, <code>escola_unidade</code>, 
              <!-- <code>data_entrada</code> -->
            </div>
          </div>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="{{ route('eventos.show', $evento) }}" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" class="btn btn-engaja">
            Importar
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- (Opcional) Link para baixar um modelo .xlsx --}}
  <div class="mt-3">
    {{-- Se criar uma rota para template, troque abaixo: --}}
    {{-- <a href="{{ route('inscricoes.template') }}" class="link-secondary small">Baixar modelo (.xlsx)</a> --}}
  </div>
</div>
@endsection
