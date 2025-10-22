@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Escalas</h1>
  <a href="{{ route('escalas.create') }}" class="btn btn-engaja">Nova escala</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Descrição</th>
          <th>Opções</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($escalas as $escala)
        <tr>
          <td class="fw-semibold">{{ $escala->descricao }}</td>
          <td>
            @php
              $opcoes = collect([$escala->opcao1, $escala->opcao2, $escala->opcao3, $escala->opcao4, $escala->opcao5])->filter()->values();
              $opcoesPreview = $opcoes->map(fn($texto) => trim(strip_tags($texto)))->filter()->implode(' | ');
            @endphp
            {{ $opcoes->isEmpty() ? '-' : $opcoesPreview }}
          </td>
          <td class="text-end">
            <a href="{{ route('escalas.show', $escala) }}" class="btn btn-sm btn-outline-primary">Ver</a>
            <a href="{{ route('escalas.edit', $escala) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form action="{{ route('escalas.destroy', $escala) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Tem certeza que deseja excluir esta escala?')">Excluir</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="3" class="text-center text-muted py-4">Nenhuma escala cadastrada.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $escalas->links() }}
</div>
@endsection
