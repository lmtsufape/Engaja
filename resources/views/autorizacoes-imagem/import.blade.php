@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="mb-3">
    <h1 class="h4 fw-bold text-engaja mb-1">Importar autorizações de imagem em lote</h1>
    <p class="text-muted mb-0">Ação pedagógica: <strong>{{ $evento->nome }}</strong></p>
  </div>

  <div class="alert alert-info">
    Envie a planilha com os participantes que assinaram a autorização física de uso de imagem nesta ação.
    Somente participantes já cadastrados no Engaja e vinculados (inscritos ou presentes) a esta ação serão autorizados.
    Nenhuma inscrição, presença ou novo cadastro de usuário será criado por esta importação.
  </div>

  @if($errors->any())
    <div class="alert alert-danger" role="alert">
      <strong>Não foi possível prosseguir. Verifique os avisos abaixo:</strong>
      <ul class="mb-0 mt-2">
        @foreach($errors->all() as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <h2 class="h6 fw-bold mb-2">Instruções da planilha</h2>
      <ul class="text-muted small mb-3">
        <li>Formatos aceitos: <strong>.xlsx</strong>, <strong>.xls</strong> ou <strong>.csv</strong> (até 20 MB e 5.000 linhas).</li>
        <li>Informe as colunas <strong>nome</strong> e <strong>cpf</strong> nas primeiras linhas da planilha.</li>
        <li>Mantenha a coluna <strong>cpf</strong> como texto para preservar zeros à esquerda. Pontos e traços são ignorados automaticamente.</li>
        <li>O cruzamento é feito pelo CPF. Caso um participante já tenha recusado anteriormente nesta ação, a confirmação atualizará o status para <strong>Autorizada</strong> mantendo o histórico.</li>
      </ul>
      <a class="btn btn-sm btn-outline-primary" href="{{ route('autorizacoes-imagem.import.modelo', $evento) }}">
        Baixar modelo da planilha (.xlsx)
      </a>
    </div>
  </div>

  <form method="POST" action="{{ route('autorizacoes-imagem.import.upload', $evento) }}" enctype="multipart/form-data" class="card shadow-sm">
    @csrf
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label fw-semibold" for="your_file">Planilha de autorizações de imagem</label>
        <input class="form-control" id="your_file" name="your_file" type="file" accept=".xlsx,.xls,.csv" required>
      </div>
      <div class="d-flex justify-content-between gap-2">
        <a href="{{ route('autorizacoes-imagem.index', ['evento_id' => $evento->id]) }}" class="btn btn-outline-secondary">Voltar</a>
        <button type="submit" class="btn btn-engaja">Enviar e visualizar prévia</button>
      </div>
    </div>
  </form>
</div>
@endsection
