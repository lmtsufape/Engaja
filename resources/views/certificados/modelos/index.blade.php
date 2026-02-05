@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
  <div>
    <p class="text-uppercase text-muted small mb-1">Certificados</p>
    <h1 class="h4 fw-bold text-engaja mb-0">Modelos de certificado</h1>
    <div class="text-muted small">Configure textos e imagens para gerar certificados.</div>
  </div>
  <a href="{{ route('certificados.modelos.create') }}" class="btn btn-engaja">Novo modelo</a>
</div>

@if ($modelos->isEmpty())
  <div class="alert alert-info">Nenhum modelo cadastrado.</div>
@else
  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">Nome</th>
            <th>Eixo</th>
            <th class="text-end pe-4">Ações</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($modelos as $m)
            <tr>
              <td class="ps-4">
                <div class="fw-semibold">{{ $m->nome }}</div>
                @if($m->descricao)
                  <div class="text-muted small">{{ \Illuminate\Support\Str::limit($m->descricao, 80) }}</div>
                @endif
              </td>
              <td>{{ $m->eixo->nome ?? '—' }}</td>
              <td class="text-end pe-4">
                <div class="d-inline-flex gap-2">
                  <a href="{{ route('certificados.modelos.edit', $m) }}" class="btn btn-sm btn-engaja">Editar</a>
                  <form action="{{ route('certificados.modelos.destroy', $m) }}" method="POST" onsubmit="return confirm('Remover este modelo?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-footer bg-white">
      {{ $modelos->links() }}
    </div>
  </div>
@endif
@endsection
