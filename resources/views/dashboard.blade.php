@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Dashboard</h1>
    @can('evento.criar')
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Ação pedagógica (evento)</label>
                    <select name="evento_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach($eventos as $id => $nome)
                        <option value="{{ $id }}" @selected(request('evento_id')==$id)>{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">De</label>
                    <input type="date" name="de" value="{{ request('de') }}" class="form-control form-control-sm">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Até</label>
                    <input type="date" name="ate" value="{{ request('ate') }}" class="form-control form-control-sm">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Buscar (momento/ação)</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Digite para filtrar...">
                </div>

                {{-- mantém sort/dir atuais --}}
                <input type="hidden" name="sort" value="{{ request('sort', 'dia') }}">
                <input type="hidden" name="dir" value="{{ request('dir', 'desc') }}">

                <div class="col-md-auto d-flex gap-2">
                    <button class="btn btn-primary btn-sm">Filtrar</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
                </div>
            </form>
        </div>


        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        @php
                        // helper inline para montar link de ordenação
                        function sort_link($label,$key){
                        $curr = request('sort','dia');
                        $dir = request('dir','desc') === 'asc' ? 'asc' : 'desc';
                        $next = ($curr===$key && $dir==='asc') ? 'desc' : 'asc';
                        $params = array_merge(request()->except('page'), ['sort'=>$key,'dir'=>$next]);
                        $url = request()->url().'?'.http_build_query($params);
                        $is = $curr===$key;
                        $arrow = $is ? ($dir==='asc' ? '↑' : '↓') : '';
                        return '<a href="'.$url.'" class="text-decoration-none">'.$label.' <span class="text-muted">'.$arrow.'</span></a>';
                        }
                        @endphp
                        <tr>
                            <th style="min-width:110px;">{!! sort_link('Data','dia') !!}</th>
                            <th style="min-width:80px;">{!! sort_link('Hora','hora') !!}</th>
                            <th>{!! sort_link('Momento','momento') !!}</th>
                            <th>{!! sort_link('Ação pedagógica','acao') !!}</th>
                            <th class="text-end" style="min-width:90px;">{!! sort_link('Presentes','presentes') !!}</th>
                            <th class="text-end" style="min-width:90px;">{!! sort_link('Ausentes','ausentes') !!}</th>
                            <th class="text-end" style="min-width:90px;">{!! sort_link('Total','total') !!}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($atividades as $a)
                        @php
                        $data = \Carbon\Carbon::parse($a->dia)->format('d/m/Y');
                        $hora = \Illuminate\Support\Str::of($a->hora_inicio)->substr(0,5);
                        @endphp
                        <tr>
                            <td>{{ $data }}</td>
                            <td>{{ $hora }}</td>
                            <td>{{ $a->descricao ?? 'Momento' }}</td>
                            <td>{{ $a->evento_nome ?? $a->evento->nome ?? '—' }}</td>
                            <td class="text-end"><span class="badge bg-success">{{ $a->presentes_count }}</span></td>
                            <td class="text-end"><span class="badge bg-secondary">{{ $a->ausentes_count }}</span></td>
                            <td class="text-end fw-semibold">{{ $a->presencas_total }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted p-4">Nenhuma atividade encontrada.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($atividades->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                Exibindo {{ $atividades->count() }} de {{ $atividades->total() }}
            </div>
            {{ $atividades->links() }}
        </div>
        @endif
    </div>
    @endcan
</div>
@endsection