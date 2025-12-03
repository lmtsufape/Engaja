@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
  <div>
    <p class="text-uppercase text-muted small mb-1">Administracao</p>
    <h1 class="h4 fw-bold text-engaja mb-0">Gerenciar usuários</h1>
    {{-- <div class="text-muted small">Disponivel apenas para administradores e gestores.</div> --}}
  </div>
  <form method="GET" action="{{ route('usuarios.index') }}" class="d-flex gap-2">
    <input type="text" name="q" value="{{ $search ?? '' }}" class="form-control" placeholder="Buscar por nome ou e-mail" aria-label="Buscar usuarios">
    <button class="btn btn-engaja" type="submit">Buscar</button>
  </form>
</div>

@if ($users->isEmpty())
  <div class="alert alert-info">
    @if (!empty($search))
      Nenhum usuario encontrado para "{{ $search }}".
    @else
      Nao ha usuarios editaveis no momento.
    @endif
  </div>
@else
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Nome</th>
              <th>E-mail</th>
              <th>CPF</th>
              <th>Telefone</th>
              <th class="text-end pe-4">Acao</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($users as $u)
              @php
                $cpfRaw = $u->participante->cpf ?? null;
                $cpfFmt = $cpfRaw ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfRaw) : '--';
                $telRaw = $u->participante->telefone ?? null;
                $telFmt = $telRaw
                  ? preg_replace('/(\d{2})(\d{4,5})(\d{4})/', '($1) $2-$3', $telRaw)
                  : '--';
              @endphp
              <tr>
                <td class="ps-4">
                  <div class="fw-semibold">{{ $u->name }}</div>
                </td>
                <td>{{ $u->email }}</td>
                <td>{{ $cpfFmt }}</td>
                <td>{{ $telFmt }}</td>
                <td class="text-end pe-4">
                  <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-sm btn-engaja">
                    Editar
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer bg-white">
      {{ $users->links() }}
    </div>
  </div>
@endif
@endsection
