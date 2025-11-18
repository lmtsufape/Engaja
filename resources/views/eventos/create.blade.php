@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-engaja mb-0">Nova ação pedagógica</h1>
        <a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary">Voltar</a>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
        <strong>Ops!</strong> Verifique os campos abaixo.
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('eventos.store') }}" class="row g-3" enctype="multipart/form-data">
                @csrf
                {{-- Eixo --}}
                <div class="col-md-6">
                    <label for="eixo_id" class="form-label">Eixo <span class="text-danger">*</span></label>
                    <select id="eixo_id" name="eixo_id"
                        class="form-select @error('eixo_id') is-invalid @enderror" required>
                        <option value="">Selecione…</option>
                        @foreach ($eixos as $eixo)
                        <option value="{{ $eixo->id }}" @selected(old('eixo_id')==$eixo->id)>
                            {{ $eixo->nome }}
                        </option>
                        @endforeach
                    </select>
                    @error('eixo_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Nome --}}
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome da ação pedagógica <span class="text-danger">*</span></label>
                    <input id="nome" name="nome" type="text"
                        value="{{ old('nome') }}"
                        class="form-control @error('nome') is-invalid @enderror" required>
                    @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Tipo --}}
                <div class="col-md-4">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select id="tipo" name="tipo" class="form-select @error('tipo') is-invalid @enderror">
                        <option value="">Selecione...</option>
                        <option value="Cartas para Esperançar" @selected(old('tipo')=="Cartas para Esperançar" )>Cartas para Esperançar</option>
                        <option value="Curso Como Alfabetizar com Paulo Freire" @selected(old('tipo')=="Curso Como Alfabetizar com Paulo Freire" )>
                            Curso Como Alfabetizar com Paulo Freire
                        </option>
                        <option value="Encontros Escuta Territorial" @selected(old('tipo')=="Encontros Escuta Territorial" )>Encontros Escuta Territorial</option>
                        <option value="Encontros de Educandos" @selected(old('tipo')=="Encontros de Educandos" )>Encontros de Educandos</option>
                        <option value="Encontros de Formação" @selected(old('tipo')=="Encontros de Formação" )>Encontros de Formação</option>
                        <option value="Feira Pedagógica, Artístico-Cultural com Educandos" @selected(old('tipo')=="Feira Pedagógica, Artístico-Cultural com Educandos" )>
                            Feira Pedagógica, Artístico-Cultural com Educandos
                        </option>
                        <option value="Lives e Webinars" @selected(old('tipo')=="Lives e Webinars" )>Lives e Webinars</option>
                        <option value="Reunião de Assessoria" @selected(old('tipo')=="Reunião de Assessoria" )>Reunião de Assessoria</option>
                        <option value="Seminários de Práticas" @selected(old('tipo')=="Seminários de Práticas" )>Seminários de Práticas</option>
                        <option value="Veja as Palavras" @selected(old('tipo')=="Veja as Palavras" )>Veja as Palavras</option>
                    </select>
                    @error('tipo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Modalidade --}}
                <div class="col-md-4">
                    <label for="modalidade" class="form-label">Modalidade</label>
                    <select id="modalidade" name="modalidade" class="form-select @error('modalidade') is-invalid @enderror">
                        <option value="">Selecione...</option>
                        <option value="Presencial" @selected(old('modalidade')=="Presencial" )>Presencial</option>
                        <option value="Online" @selected(old('modalidade')=="Online" )>Online</option>
                        <option value="Híbrido" @selected(old('modalidade')=="Híbrido" )>Híbrido</option>
                    </select>
                    @error('modalidade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Período --}}
                <div class="col-md-6">
                    <label for="data_inicio" class="form-label">Início</label>
                    <input id="data_inicio" name="data_inicio" type="date"
                        value="{{ old('data_inicio') }}"
                        class="form-control @error('data_inicio') is-invalid @enderror">
                    @error('data_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="data_fim" class="form-label">Fim</label>
                    <input id="data_fim" name="data_fim" type="date"
                        value="{{ old('data_fim') }}"
                        class="form-control @error('data_fim') is-invalid @enderror">
                    @error('data_fim') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Local --}}
                <div class="col-md-6">
                    <label for="local" class="form-label">Local</label>
                    <input id="local" name="local" type="text"
                        value="{{ old('local') }}"
                        class="form-control @error('local') is-invalid @enderror"
                        placeholder="Auditório Central / Link da plataforma etc.">
                    @error('local') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Link (para eventos online) --}}
                <div class="col-md-6">
                    <label for="link" class="form-label">Link (se for online)</label>
                    <input id="link" name="link" type="url"
                        value="{{ old('link') }}"
                        class="form-control @error('link') is-invalid @enderror"
                        placeholder="https://…">
                    @error('link') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Imagem do evento --}}
                <div class="col-md-6">
                    <label for="imagem" class="form-label">Imagem da ação pedagógica</label>
                    <input id="imagem" name="imagem" type="file"
                        class="form-control @error('imagem') is-invalid @enderror"
                        accept="image/*">
                    <div class="form-text">Formatos: JPG, PNG, SVG | Tamanho máx. recomendado: 2MB</div>
                    @error('imagem') <div class="invalid-feedback">{{ $message }}</div> @enderror

                    {{-- Preview simples --}}
                    <img id="preview-imagem" class="mt-2 img-fluid d-none rounded" alt="Pré-visualização">
                </div>

                {{-- Objetivo --}}
                <div class="col-12">
                    <label for="objetivo" class="form-label">Objetivo</label>
                    <textarea id="objetivo" name="objetivo" rows="3"
                        class="form-control @error('objetivo') is-invalid @enderror"
                        placeholder="Descreva o objetivo da ação pedagógica…">{{ old('objetivo') }}</textarea>
                    @error('objetivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Resumo --}}
                <div class="col-12">
                    <label for="resumo" class="form-label">Resumo</label>
                    <textarea id="resumo" name="resumo" rows="3"
                        class="form-control @error('resumo') is-invalid @enderror"
                        placeholder="Breve descrição para divulgação…">{{ old('resumo') }}</textarea>
                    @error('resumo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-engaja">Salvar ação pedagógica</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('imagem')?.addEventListener('change', function(e) {
        const file = e.target.files?.[0];
        const img = document.getElementById('preview-imagem');
        if (file && img) {
            img.src = URL.createObjectURL(file);
            img.classList.remove('d-none');
        }
    });
</script>
@endsection
