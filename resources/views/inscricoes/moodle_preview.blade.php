@extends('layouts.app')

@section('content')
<style>
  .moodle-preview-table {
    min-width: 1200px;
  }

  .moodle-preview-table th,
  .moodle-preview-table td {
    vertical-align: middle;
  }

  .moodle-preview-table .sticky-col {
    position: sticky;
    left: 0;
    z-index: 3;
    background: #fff;
  }

  .moodle-preview-table .sticky-col-2 {
    position: sticky;
    left: 54px;
    z-index: 3;
    background: #fff;
    min-width: 260px;
  }

  .moodle-preview-table .sticky-col-3 {
    position: sticky;
    left: 314px;
    z-index: 3;
    background: #fff;
    min-width: 260px;
  }

  .moodle-preview-table thead .sticky-col,
  .moodle-preview-table thead .sticky-col-2,
  .moodle-preview-table thead .sticky-col-3 {
    background: var(--bs-table-bg, #f8f9fa);
    z-index: 4;
  }

  .moment-col-header {
    min-width: 150px;
    max-width: 180px;
    white-space: normal;
    word-break: break-word;
    line-height: 1.25;
  }

  .status-chip {
    display: inline-block;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
  }

  .status-chip-success {
    background: #d1fae5;
    color: #065f46;
  }

  .status-chip-fail {
    background: #dbeafe;
    color: #1e3a8a;
  }

  .status-chip-empty {
    background: #f3f4f6;
    color: #4b5563;
  }

  .new-users-modal .modal-body {
    max-height: 58vh;
    overflow-y: auto;
  }

  .new-users-list {
    max-height: 40vh;
    overflow-y: auto;
    padding-right: 0.35rem;
  }
</style>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h1 class="h4 mb-1">Pré-visualização da Importação Moodle</h1>
      <div class="text-muted small">Ação pedagógica: <strong>{{ $evento->nome }}</strong></div>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('inscricoes.moodle.import', $evento) }}" class="btn btn-outline-secondary">Voltar</a>
      <form
        id="moodle-confirm-form"
        method="POST"
        action="{{ route('inscricoes.moodle.confirm', $evento) }}"
        data-has-new-users="{{ $newUsers->isNotEmpty() ? '1' : '0' }}">
        @csrf
        <input type="hidden" name="session_key" value="{{ $sessionKey }}">
        <button class="btn btn-primary" {{ $errorsList->isNotEmpty() ? 'disabled' : '' }}>
          Confirmar importação Moodle
        </button>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Linhas válidas</div>
          <div class="fs-4 fw-semibold">{{ $resumo['participants_rows'] ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Emails únicos</div>
          <div class="fs-4 fw-semibold">{{ $resumo['participants_unique_email'] ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Momentos</div>
          <div class="fs-4 fw-semibold">{{ $resumo['momentos_total'] ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Inconsistências</div>
          <div class="fs-4 fw-semibold {{ ($resumo['errors_total'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
            {{ $resumo['errors_total'] ?? 0 }}
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Usuários novos no Engaja</div>
          <div class="fs-4 fw-semibold {{ ($resumo['new_users_count'] ?? 0) > 0 ? 'text-warning' : 'text-success' }}">
            {{ $resumo['new_users_count'] ?? 0 }}
          </div>
        </div>
      </div>
    </div>
  </div>

  @if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  @if($newUsers->isNotEmpty())
  <div class="card border-warning shadow-sm mb-3">
    <div class="card-header bg-warning-subtle text-warning-emphasis fw-semibold">
      Usuários que serão cadastrados agora (não encontrados no Engaja)
    </div>
    <div class="card-body">
      <p class="mb-2">
        Foram encontrados <strong>{{ $newUsers->count() }}</strong> usuário(s) da planilha sem cadastro prévio no Engaja.
      </p>
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
          <thead>
            <tr>
              <th>Nome</th>
              <th>Email</th>
            </tr>
          </thead>
          <tbody>
            @foreach($newUsers as $newUser)
            <tr>
              <td>{{ $newUser['nome'] }}</td>
              <td>{{ $newUser['email'] }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  @if($errorsList->isNotEmpty())
  <div class="alert alert-danger">
    <div class="fw-semibold mb-1">Problemas encontrados</div>
    <ul class="mb-0">
      @foreach($errorsList as $message)
      <li>{{ $message }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  @if($conflicts->isNotEmpty())
  <div class="card border-danger shadow-sm mb-3">
    <div class="card-header bg-danger-subtle text-danger-emphasis fw-semibold">Conflitos de nome/email com usuários existentes</div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>Linha</th>
            <th>Email</th>
            <th>Nome na planilha</th>
            <th>Nome no sistema</th>
          </tr>
        </thead>
        <tbody>
          @foreach($conflicts as $conflict)
          <tr>
            <td>{{ $conflict['line'] }}</td>
            <td>{{ $conflict['email'] }}</td>
            <td>{{ $conflict['sheet_name'] }}</td>
            <td>{{ $conflict['system_name'] }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  <div class="card shadow-sm mb-3">
    <div class="card-header fw-semibold">Momentos identificados</div>
    <div class="table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead>
          <tr>
            <th>Momento</th>
            <th>Carga horária</th>
            <th>Na planilha de participantes</th>
            <th>Na planilha de cargas</th>
          </tr>
        </thead>
        <tbody>
          @forelse($momentos as $momento)
          <tr>
            <td>{{ $momento['nome'] }}</td>
            <td>{{ $momento['carga_horaria'] ?? '—' }}</td>
            <td>{{ !empty($momento['em_participantes']) ? 'Sim' : 'Não' }}</td>
            <td>{{ !empty($momento['em_cargas']) ? 'Sim' : 'Não' }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="4" class="text-center text-muted">Nenhum momento identificado.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-bordered align-middle bg-white moodle-preview-table">
      <thead class="table-light">
        <tr>
          <th class="sticky-col">Linha</th>
          <th class="sticky-col-2">Nome</th>
          <th class="sticky-col-3">Email</th>
          @foreach($momentos as $momento)
          <th class="moment-col-header" title="{{ $momento['nome'] }}">{{ $momento['nome'] }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $row)
        <tr>
          <td class="sticky-col">{{ $row['line'] ?? '—' }}</td>
          <td class="sticky-col-2">{{ $row['nome'] }}</td>
          <td class="sticky-col-3">{{ $row['email'] }}</td>
          @foreach($momentos as $momento)
            @php $status = $row['status_por_momento'][$momento['nome']] ?? null; @endphp
            <td>
              @if($status === true)
                <span class="status-chip status-chip-success">Concluiu</span>
              @endif
            </td>
          @endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-end mt-3">
    {{ $rows->appends(['session_key' => $sessionKey, 'per_page' => $rows->perPage()])->links() }}
  </div>
</div>

@if($newUsers->isNotEmpty() && $errorsList->isEmpty())
<div class="modal fade" id="newUsersWarningModal" tabindex="-1" aria-labelledby="newUsersWarningModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable new-users-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="newUsersWarningModalLabel">Atenção: novos usuários serão cadastrados</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">
          <strong>{{ $newUsers->count() }}</strong> usuário(s) da planilha não existem no Engaja e serão cadastrados agora.
        </p>
        <p class="mb-2">Nomes:</p>
        <ul class="mb-0 new-users-list">
          @foreach($newUsers as $newUser)
          <li>{{ $newUser['nome'] }} ({{ $newUser['email'] }})</li>
          @endforeach
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Revisar</button>
        <button type="button" class="btn btn-primary btn-sm" id="new-users-confirm-proceed">Salvar</button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('moodle-confirm-form');
    const proceedBtn = document.getElementById('new-users-confirm-proceed');
    const modalElement = document.getElementById('newUsersWarningModal');

    if (!form || !modalElement || form.dataset.hasNewUsers !== '1') {
      return;
    }

    let allowSubmit = false;

    form.addEventListener('submit', function (event) {
      if (allowSubmit) {
        return;
      }

      event.preventDefault();

      if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
        const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
        return;
      }

      const fallbackConfirm = window.confirm('Existem usuários novos que serão cadastrados agora. Deseja continuar?');
      if (fallbackConfirm) {
        allowSubmit = true;
        form.submit();
      }
    });

    if (proceedBtn) {
      proceedBtn.addEventListener('click', function () {
        allowSubmit = true;
        form.submit();
      });
    }
  });
</script>
@endif
@endsection
