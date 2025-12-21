@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <p class="text-uppercase text-muted small mb-1">Administração</p>
      <h1 class="h4 fw-bold mb-1">Certificados emitidos</h1>
      <p class="text-muted mb-0">Lista de todos os certificados já emitidos no sistema.</p>
    </div>
  </div>

  <div class="table-responsive shadow-sm rounded-3 bg-white">
    <table class="table align-middle mb-0 cert-table">
      <thead>
        <tr>
          <th>Participante</th>
          <th class="text-start">Ação pedagógica</th>
          <th class="text-center">Modelo</th>
          <th class="text-center">Carga horária</th>
          <th class="text-center">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($certificados as $cert)
          <tr>
            <td>{{ $cert->participante?->user?->name ?? '-' }}</td>
            <td class="text-startr">{{ $cert->evento_nome ?? '-' }}</td>
            <td class="text-center">{{ $cert->modelo?->nome ?? '-' }}</td>
            <td class="text-center">{{ $cert->carga_horaria ?? '-' }}h</td>
            <td class="text-center">
              <div class="d-flex gap-2 justify-content-center">
                <a href="{{ route('certificados.download', $cert) }}" class="btn btn-engaja btn-sm px-3">
                  Baixar PDF
                </a>
                <a href="{{ route('certificados.edit', $cert) }}" class="btn btn-outline-secondary btn-sm px-3">
                  Editar
                </a>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center text-muted py-4">Nenhum certificado emitido até o momento.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-center mt-3">
    {{ $certificados->links() }}
  </div>
</div>

@push('styles')
<style>
  .cert-table thead th {
    background: #f5f6fa;
    font-weight: 700;
    border-bottom: 1px solid #e2e5ec;
  }
  .cert-table tbody tr:last-child td {
    border-bottom: none;
  }
  .cert-table td, .cert-table th {
    border-color: #e2e5ec;
    vertical-align: middle;
  }
  .btn-engaja {
    background-color: #4a0e4e;
    color: #fff;
    border: 1px solid #4a0e4e;
  }
  .btn-engaja:hover {
    background-color: #3c0b3f;
    color: #fff;
    border-color: #3c0b3f;
  }
</style>
@endpush
@endsection
