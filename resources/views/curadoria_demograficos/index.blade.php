@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card ev-card">
                <div class="card-header bg-engaja text-white">
                    <h5 class="mb-0 fw-bold">Curadoria de Dados Demográficos</h5>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <p class="mb-4">
                        Abaixo estão os dados demográficos preenchidos durante a confirmação de presença via QR Code. 
                        Revise e vincule os dados ao cadastro do usuário.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuário (E-mail / CPF)</th>
                                    <th>Gênero</th>
                                    <th>Raça / Cor</th>
                                    <th>Comunidade Tradicional</th>
                                    <th>Faixa Etária</th>
                                    <th>PcD</th>
                                    <th>Orientação Sexual</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($curadorias as $curadoria)
                                    <tr>
                                        <td>
                                            <strong>{{ $curadoria->user->name ?? 'N/A' }}</strong><br>
                                            <small class="text-muted">{{ $curadoria->user->email ?? '' }}</small>
                                            @if($curadoria->user && $curadoria->user->demograficosCompletos())
                                                <br><span class="badge bg-warning text-dark mt-1" title="Este usuário já possui os dados demográficos completamente preenchidos no sistema. Vincular irá sobrescrever."> Dados já completos</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $curadoria->identidade_genero }}
                                            @if($curadoria->identidade_genero === 'Outro' && $curadoria->identidade_genero_outro)
                                                <br><small class="text-muted">({{ $curadoria->identidade_genero_outro }})</small>
                                            @endif
                                        </td>
                                        <td>{{ $curadoria->raca_cor }}</td>
                                        <td>
                                            {{ $curadoria->comunidade_tradicional }}
                                            @if($curadoria->comunidade_tradicional === 'Outro' && $curadoria->comunidade_tradicional_outro)
                                                <br><small class="text-muted">({{ $curadoria->comunidade_tradicional_outro }})</small>
                                            @endif
                                        </td>
                                        <td>{{ $curadoria->faixa_etaria }}</td>
                                        <td>{{ $curadoria->pcd }}</td>
                                        <td>
                                            {{ $curadoria->orientacao_sexual }}
                                            @if($curadoria->orientacao_sexual === 'Outra' && $curadoria->orientacao_sexual_outra)
                                                <br><small class="text-muted">({{ $curadoria->orientacao_sexual_outra }})</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <form action="{{ route('curadoria.vincular', $curadoria->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-engaja" title="Vincular dados ao usuário" onclick="return confirm('Deseja realmente vincular esses dados ao usuário?')">
                                                        Vincular
                                                    </button>
                                                </form>

                                                <form action="{{ route('curadoria.destroy', $curadoria->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir este registro" onclick="return confirm('Deseja realmente excluir este registro?')">
                                                        Excluir
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Nenhum dado pendente para curadoria.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $curadorias->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
