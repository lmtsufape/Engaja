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

                <div class="col-md-2">
                    <label class="form-label mb-1">Por página</label>
                    <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="25" @selected(request('per_page', 25)==25)>25</option>
                        <option value="50" @selected(request('per_page')==50)>50</option>
                        <option value="100" @selected(request('per_page')==100)>100</option>
                    </select>
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
            {{-- barra de ações da tabela --}}
            <div class="d-flex justify-content-end gap-2 p-2 border-bottom bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm js-toggle-all" data-action="show">
                    Expandir todos
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm js-toggle-all" data-action="hide">
                    Recolher todos
                </button>
                <a href="{{ route('dashboard.export', request()->query()) }}"
                    class="btn btn-outline-primary btn-sm">
                    Baixar PDF
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        @php
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
                            <th>{!! sort_link('Município','municipio') !!}</th>
                            <th>{!! sort_link('Ação pedagógica','acao') !!}</th>
                            <th class="text-end" style="min-width:90px;">{!! sort_link('Inscritos','inscritos') !!}</th>
                            <th class="text-end" style="min-width:90px;">{!! sort_link('Presentes','presentes') !!}</th>
                            <th class="text-end" style="min-width:90px;">{!! sort_link('Ausentes','ausentes') !!}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($atividades as $a)
                        @php
                        $data = \Carbon\Carbon::parse($a->dia)->format('d/m/Y');
                        $hora = \Illuminate\Support\Str::of($a->hora_inicio)->substr(0,5);
                        $collapseId = 'pres-' . $a->id;
                        $presentes = collect($a->presencas ?? []);
                        $inscricoes = collect($a->inscricoes ?? []);
                        $presentesIds = $presentes->pluck('inscricao_id')->filter()->unique();
                        $ausentes = $inscricoes->filter(fn($insc) => !$presentesIds->contains($insc->id))->values();
                        $inscritosCount = $inscricoes->count();
                        $presentesCount = $presentesIds->count();
                        $ausentesCount = $ausentes->count();
                        @endphp

                        <tr>
                            <td>{{ $data }}</td>
                            <td>{{ $hora }}</td>
                            <td>{{ $a->descricao ?? 'Momento' }}</td>
                            <td>{{ $a->municipio?->nome_com_estado ?? '-' }}</td>
                            <td>{{ $a->evento_nome ?? $a->evento->nome ?? '-' }}</td>
                            <td class="text-end fw-semibold">{{ $inscritosCount }}</td>
                            {{-- Gatilho do accordion na coluna Presentes --}}
                            <td class="text-end">
                                <a class="badge bg-success text-decoration-none"
                                    data-bs-toggle="collapse"
                                    href="#{{ $collapseId }}"
                                    role="button"
                                    data-focus-target="#{{ $collapseId }}-presentes"
                                    aria-expanded="false"
                                    aria-controls="{{ $collapseId }}">
                                    {{ $presentesCount }}
                                </a>
                            </td>
                            <td class="text-end">
                                @if($ausentesCount > 0)
                                    <a class="badge bg-warning text-dark text-decoration-none"
                                        data-bs-toggle="collapse"
                                        href="#{{ $collapseId }}"
                                        role="button"
                                        data-focus-target="#{{ $collapseId }}-ausentes"
                                        aria-expanded="false"
                                        aria-controls="{{ $collapseId }}">
                                        {{ $ausentesCount }}
                                    </a>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                        </tr>
                        {{-- Linha de detalhes: agora o .collapse fica dentro do TD --}}
                        <tr>
                            <td colspan="8" class="bg-light p-0">
                                <div id="{{ $collapseId }}" class="collapse presentes-collapse">
                                    <div class="px-3 py-2 border-bottom small text-muted">
                                        Inscritos: <strong>{{ $inscritosCount }}</strong> |
                                        Presentes: <strong>{{ $presentesCount }}</strong> |
                                        Ausentes: <strong>{{ $ausentesCount }}</strong>
                                    </div>

                                    <div id="{{ $collapseId }}-presentes" class="p-2">
                                        <div class="section-title fw-bold">Presentes</div>
                                        @if($presentes->isEmpty())
                                            <div class="text-muted small p-3">Nenhuma presença registrada.</div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="table-primary">
                                                        <tr>
                                                            <th style="width: 35%;">Nome</th>
                                                            <th style="width: 30%;">E-mail</th>
                                                            <th style="width: 18%;">CPF</th>
                                                            <th style="width: 17%;">Tag</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($presentes as $p)
                                                            @php
                                                                $insc = optional($p->inscricao);
                                                                $part = optional($insc->participante);
                                                                $user = optional($part->user);
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $user->name ?? 'Participante #'.$part->id }}</td>
                                                                <td>{{ $user->email ?? '-' }}</td>
                                                                <td>{{ $part->cpf ?: '-' }}</td>
                                                                <td>{{ $part->tag ?: '-' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>

                                    <div id="{{ $collapseId }}-ausentes" class="p-2">
                                        <div class="section-title fw-bold">Ausentes</div>
                                        @if($ausentes->isEmpty())
                                            <div class="text-muted small p-3">Nenhum ausente registrado.</div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="table-secondary">
                                                        <tr>
                                                            <th style="width: 35%;">Nome</th>
                                                            <th style="width: 30%;">E-mail</th>
                                                            <th style="width: 18%;">CPF</th>
                                                            <th style="width: 17%;">Tag</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($ausentes as $insc)
                                                            @php
                                                                $part = optional($insc->participante);
                                                                $user = optional($part->user);
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $user->name ?? 'Participante #'.$part->id }}</td>
                                                                <td>{{ $user->email ?? '-' }}</td>
                                                                <td>{{ $part->cpf ?: '-' }}</td>
                                                                <td>{{ $part->tag ?: '-' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
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
            {{ $atividades->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
    @endcan
</div>

{{-- Script para expandir/recolher todos --}}
<script>

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.js-toggle-all');
        if (!btn) return;

        const action = btn.dataset.action; // 'show' | 'hide'
        const items = document.querySelectorAll('.presentes-collapse');

        const hasBootstrap = window.bootstrap && bootstrap.Collapse;

        items.forEach(function(el) {
            if (hasBootstrap) {
                const instance = bootstrap.Collapse.getOrCreateInstance(el, {
                    toggle: false
                });
                if (action === 'show') instance.show();
                else instance.hide();
            } else {
                if (action === 'show') el.classList.add('show');
                else el.classList.remove('show');
            }
        });
    });


    document.addEventListener('click', function(e) {
        const focusLink = e.target.closest('[data-focus-target]');
        if (!focusLink) return;

        const collapseSelector = focusLink.getAttribute('href');
        if (!collapseSelector || !collapseSelector.startsWith('#')) {
            return;
        }

        const collapseEl = document.querySelector(collapseSelector);
        if (!collapseEl) {
            return;
        }

        collapseEl.dataset.focusTarget = focusLink.dataset.focusTarget || '';

        if (!window.bootstrap || !bootstrap.Collapse) {
            setTimeout(function () {
                if (!collapseEl.classList.contains('show')) {
                    return;
                }
                const focusSel = collapseEl.dataset.focusTarget;
                if (!focusSel) return;
                const focusEl = collapseEl.querySelector(focusSel);
                if (focusEl) {
                    focusEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 200);
        }
    });

    if (window.bootstrap && bootstrap.Collapse) {
        document.addEventListener('shown.bs.collapse', function (event) {
            const collapseEl = event.target;
            const focusSel = collapseEl.dataset.focusTarget;
            if (!focusSel) return;
            const focusEl = collapseEl.querySelector(focusSel);
            if (focusEl) {
                focusEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            delete collapseEl.dataset.focusTarget;
        });
    }
</script>

@endsection
