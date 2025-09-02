@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Inscritos — {{ $evento->nome }}</h1>
    <a href="{{ route('inscricoes.import', $evento) }}" class="btn btn-sm btn-outline-primary">Importar inscrições</a>
  </div>

  {{-- Filtros --}}
  <form method="GET" action="{{ route('inscricoes.inscritos', $evento) }}" class="row g-2 align-items-end mb-3">
    <div class="col-sm-5">
      <label class="form-label mb-1">Buscar (nome, e-mail, CPF, telefone)</label>
      <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Digite para buscar...">
    </div>
    <div class="col-sm-4">
      <label class="form-label mb-1">Município</label>
      <select name="municipio_id" class="form-select form-select-sm">
        <option value="">— Todos —</option>
        @foreach($municipios as $m)
          <option value="{{ $m->id }}" @selected((string)$municipioId === (string)$m->id)>
            {{ $m->nome_com_estado }}
          </option>
        @endforeach
      </select>
    </div>
    <div class="col-sm-2">
      <label class="form-label mb-1">Por página</label>
      <select name="per_page" class="form-select form-select-sm">
        @foreach([25,50,100,200] as $pp)
          <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-sm-1 d-grid">
      <button class="btn btn-sm btn-primary">Filtrar</button>
    </div>
  </form>

  {{-- Resumo --}}
  <div class="small text-muted mb-2">
    Total: {{ $inscritos->total() }} • Página {{ $inscritos->currentPage() }} de {{ $inscritos->lastPage() }}
  </div>

  {{-- Tabela --}}
  <div class="table-responsive">
    <table class="table table-sm align-middle table-bordered bg-white">
      <thead class="table-light">
        <tr>
          <th style="width:70px;">#ID</th>
          <th>Nome</th>
          <th>Email</th>
          <th style="min-width:120px;">CPF</th>
          <th style="min-width:120px;">Telefone</th>
          <th style="min-width:220px;">Município</th>
          <th style="min-width:140px;">Data entrada</th>
          <th style="min-width:160px;">Inscrito em</th>
          {{-- Opcional: ações (ver, remover, etc.) --}}
          {{-- <th style="width:110px;">Ações</th> --}}
        </tr>
      </thead>
      <tbody>
        @forelse($inscritos as $p)
          <tr>
            <td>{{ $p->id }}</td>
            <td>{{ $p->user->name ?? '-' }}</td>
            <td>{{ $p->user->email ?? '-' }}</td>
            <td>{{ $p->cpf ?? '-' }}</td>
            <td>{{ $p->telefone ?? '-' }}</td>
            <td>
              @if($p->municipio)
                {{ $p->municipio->nome_com_estado }}
              @else
                —
              @endif
            </td>
            <td>{{ $p->data_entrada ?? '—' }}</td>
            <td>
              {{-- se você definiu ->as('inscricao') na relação --}}
              {{ optional($p->inscricao->created_at ?? $p->pivot->created_at ?? null)->format('d/m/Y H:i') ?? '—' }}
            </td>

            {{-- Exemplo de ação: remover inscrição (soft delete no pivô) --}}
            {{--
            <td>
              <form method="POST" action="{{ route('inscricoes.remover', [$evento, $p->id]) }}" onsubmit="return confirm('Remover inscrição?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Remover</button>
              </form>
            </td>
            --}}
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-muted">Nenhum inscrito encontrado.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between align-items-center">
    <div class="small text-muted">Exibindo {{ $inscritos->count() }} de {{ $inscritos->total() }}</div>
    {{ $inscritos->links() }}
  </div>
</div>
@endsection
