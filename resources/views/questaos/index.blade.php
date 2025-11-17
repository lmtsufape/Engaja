@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Questões</h1>
  <a href="{{ route('questaos.create') }}" class="btn btn-engaja">Nova questão</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Texto</th>
          <th>Indicador</th>
          <th>Evidência</th>
          <th>Tipo</th>
          <th>Escala</th>
          <th>Modelo</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($questaos as $questao)
        <tr>
          <td class="fw-semibold">{{ \Illuminate\Support\Str::limit($questao->texto, 80) }}</td>
          <td>{{ $questao->indicador->descricao ?? '—' }}</td>
          <td>{{ $questao->evidencia->descricao ?? '—' }}</td>
          <td>{{ ucfirst($questao->tipo) }}</td>
          <td>{{ $questao->escala->descricao ?? '—' }}</td>
          <td>{{ $questao->template->nome ?? '—' }}</td>
          <td class="text-end">
            <a href="{{ route('questaos.show', $questao) }}" class="btn btn-sm btn-outline-primary">Ver</a>
            <a href="{{ route('questaos.edit', $questao) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form action="{{ route('questaos.destroy', $questao) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Tem certeza que deseja excluir esta questão?')">Excluir</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" class="text-center text-muted py-4">Nenhuma questão cadastrada.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $questaos->links() }}
</div>
@endsection
