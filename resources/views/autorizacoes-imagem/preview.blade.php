@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
  <div class="mb-3">
    <h1 class="h4 fw-bold text-engaja mb-1">Prévia — Importação de autorizações de imagem</h1>
    <p class="text-muted mb-0">Ação pedagógica: <strong>{{ $evento->nome }}</strong></p>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger" role="alert">
      <strong>Importação bloqueada. Corrija ou remova as pendências abaixo antes de confirmar:</strong>
      <ul class="mb-0 mt-2">
        @foreach($errors->all() as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @php
    $counts = $analysis['counts'];
  @endphp
  <div class="d-flex flex-wrap gap-3 mb-3">
    <span class="badge bg-light text-dark border fs-6 fw-normal">Total de linhas: <strong>{{ $counts['total'] }}</strong></span>
    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle fs-6 fw-normal">Novas autorizações: <strong>{{ $counts['autorizar'] }}</strong></span>
    @if($counts['alterar_recusa'] > 0)
      <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6 fw-normal">Alterar de recusa para autorizada: <strong>{{ $counts['alterar_recusa'] }}</strong></span>
    @endif
    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle fs-6 fw-normal">Já autorizadas: <strong>{{ $counts['ja_autorizadas'] }}</strong></span>
    @if($counts['duplicadas'] > 0)
      <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fs-6 fw-normal">Duplicadas na planilha: <strong>{{ $counts['duplicadas'] }}</strong></span>
    @endif
    <span class="badge {{ $counts['pendencias'] > 0 ? 'bg-danger' : 'bg-success' }} fs-6 fw-normal">Pendências bloqueantes: <strong>{{ $counts['pendencias'] }}</strong></span>
  </div>

  <section id="pendencias-autorizacao" class="card border-{{ $counts['pendencias'] > 0 ? 'danger' : 'success' }} mb-3" style="scroll-margin-top: 100px;" aria-labelledby="titulo-pendencias-autorizacao">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h2 id="titulo-pendencias-autorizacao" class="h5 mb-0">Pendências de todo o arquivo: {{ $counts['pendencias'] }} linha(s)</h2>
        @if($counts['pendencias'] > 0)
          <form method="POST" id="remover-pendencias-form" action="{{ route('autorizacoes-imagem.import.remove-pending', $evento) }}" class="m-0">
            @csrf
            <input type="hidden" name="session_key" value="{{ $sessionKey }}">
            <input type="hidden" name="per_page" value="{{ $rows->perPage() }}">
            <button type="submit" class="btn btn-sm btn-outline-danger">Remover todas as linhas com pendência</button>
          </form>
        @endif
      </div>

      @if($analysis['errors'])
        <p class="small text-muted mb-2">
          A confirmação só será liberada quando todas as pendências forem corrigidas ou removidas.
          Edite o CPF na tabela abaixo (ou marque para remover) e clique em <strong>Salvar alterações desta página</strong>.
        </p>
        <ul class="mb-0 small">
          @foreach($analysis['errors'] as $index => $message)
            @php
              $pendingPage = intdiv($index, $rows->perPage()) + 1;
            @endphp
            <li class="mb-1">
              {{ $message }}
              <a href="{{ route('autorizacoes-imagem.import.preview', ['evento' => $evento, 'session_key' => $sessionKey, 'page' => $pendingPage, 'per_page' => $rows->perPage()]) }}#editar-autorizacoes">
                Ver na página {{ $pendingPage }}
              </a>
            </li>
          @endforeach
        </ul>
      @else
        <p class="mb-0">Nenhuma pendência encontrada. Confira os dados abaixo e confirme a importação.</p>
      @endif
    </div>
  </section>

  <div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-outline-secondary" href="{{ route('autorizacoes-imagem.import', $evento) }}">Enviar outra planilha</a>
    <a class="btn btn-light border" href="{{ route('autorizacoes-imagem.index', ['evento_id' => $evento->id]) }}">Cancelar</a>
    <form method="POST" action="{{ route('autorizacoes-imagem.import.confirmar', $evento) }}" id="confirmar-importacao-autorizacoes" class="m-0">
      @csrf
      <input type="hidden" name="session_key" value="{{ $sessionKey }}">
      <button type="submit" class="btn btn-engaja" @disabled($counts['pendencias'] > 0 || $counts['unicos_validos'] === 0)>
        Confirmar importação ({{ $counts['unicos_validos'] }} participante(s))
      </button>
    </form>
  </div>

  <form method="POST" id="editar-autorizacoes" action="{{ route('autorizacoes-imagem.import.save', ['evento' => $evento, 'page' => $rows->currentPage(), 'per_page' => $rows->perPage()]) }}">
    @csrf
    <input type="hidden" name="session_key" value="{{ $sessionKey }}">

    <div class="card shadow-sm mb-3">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 110px;">Linha</th>
              <th style="min-width: 220px;">Nome na planilha</th>
              <th style="min-width: 170px;">CPF na planilha</th>
              <th style="min-width: 240px;">Participante no sistema</th>
              <th style="min-width: 150px;">Situação atual</th>
              <th style="min-width: 220px;">Resultado previsto</th>
              <th class="text-center" style="width: 90px;">Remover</th>
            </tr>
          </thead>
          <tbody>
            @foreach($rows as $index => $row)
              @php
                $item = $analysis['items'][$index];
                $participante = $item['participante'];
                $rowClass = match ($item['acao_prevista']) {
                    'erro' => 'table-danger',
                    'alterar_recusa' => 'table-warning',
                    default => '',
                };
              @endphp
              <tr class="{{ $rowClass }}">
                <td class="small fw-semibold">{{ $item['location'] }}</td>
                <td>
                  <input
                    type="text"
                    name="rows[{{ $index }}][nome]"
                    class="form-control form-control-sm"
                    value="{{ old("rows.$index.nome", $row['nome']) }}"
                    maxlength="255">
                </td>
                <td>
                  <input
                    type="text"
                    name="rows[{{ $index }}][cpf]"
                    class="form-control form-control-sm {{ $item['erro'] ? 'is-invalid' : '' }}"
                    value="{{ old("rows.$index.cpf", $row['cpf']) }}"
                    maxlength="255"
                    placeholder="Somente 11 dígitos">
                </td>
                <td>
                  @if($participante)
                    <div class="fw-semibold">{{ $participante->user->name }}</div>
                    <div class="small text-muted">{{ $participante->user->email }}</div>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                  @foreach($item['avisos'] as $aviso)
                    <div class="small text-warning-emphasis mt-1">{{ $aviso }}</div>
                  @endforeach
                </td>
                <td>
                  @if($item['situacao_atual'])
                    {{ $item['situacao_atual']->rotulo() }}
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @switch($item['acao_prevista'])
                    @case('autorizar')
                      <span class="badge bg-success">Pronta para autorizar</span>
                      @break
                    @case('alterar_recusa')
                      <span class="badge bg-warning text-dark">Alterará de Não autorizada para Autorizada</span>
                      @break
                    @case('ja_autorizada')
                      <span class="badge bg-secondary">Já autorizada (sem alteração)</span>
                      @break
                    @case('duplicada_planilha')
                      <span class="badge bg-info text-dark">Duplicada na planilha</span>
                      @break
                    @default
                      <span class="badge bg-danger">Pendência</span>
                      <div class="small text-danger mt-1">{{ $item['erro'] }}</div>
                  @endswitch
                </td>
                <td class="text-center">
                  <input
                    type="checkbox"
                    class="form-check-input"
                    name="remove[{{ $index }}]"
                    value="1"
                    aria-label="Remover {{ $item['location'] }}">
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <button type="submit" class="btn btn-success">Salvar alterações desta página</button>
      <div>{{ $rows->links() }}</div>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  let dirty = false;
  const editForm = document.getElementById('editar-autorizacoes');
  const confirmForm = document.getElementById('confirmar-importacao-autorizacoes');
  const removePendingForm = document.getElementById('remover-pendencias-form');

  if (editForm) {
    editForm.addEventListener('input', () => { dirty = true; });
    editForm.addEventListener('change', () => { dirty = true; });
    editForm.addEventListener('submit', () => { dirty = false; });
  }

  if (removePendingForm) {
    removePendingForm.addEventListener('submit', (event) => {
      if (!confirm('Remover todas as linhas com pendência desta prévia?')) {
        event.preventDefault();
        return;
      }
      dirty = false;
    });
  }

  if (confirmForm) {
    confirmForm.addEventListener('submit', (event) => {
      if (dirty) {
        event.preventDefault();
        alert('Salve as alterações desta página antes de confirmar a importação.');
      }
    });
  }

  window.addEventListener('beforeunload', (event) => {
    if (dirty) {
      event.preventDefault();
      event.returnValue = '';
    }
  });
});
</script>
@endsection
