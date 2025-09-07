@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-engaja mb-0">Editar ação pedagógica</h1>
        <a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary">Voltar</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger"><strong>Ops!</strong> Verifique os campos abaixo.</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            {{-- IMPORTANTE: enctype para upload --}}
            <form method="POST" action="{{ route('eventos.update', $evento) }}" class="row g-3" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Eixo --}}
                <div class="col-md-6">
                    <label for="eixo_id" class="form-label">Eixo <span class="text-danger">*</span></label>
                    <select id="eixo_id" name="eixo_id"
                            class="form-select @error('eixo_id') is-invalid @enderror" required>
                        @foreach ($eixos as $eixo)
                            <option value="{{ $eixo->id }}" @selected(old('eixo_id', $evento->eixo_id) == $eixo->id)>{{ $eixo->nome }}</option>
                        @endforeach
                    </select>
                    @error('eixo_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Nome --}}
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome da ação pedagógica <span class="text-danger">*</span></label>
                    <input id="nome" name="nome" type="text"
                           value="{{ old('nome', $evento->nome) }}"
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
                    @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Modalidade --}}
                <div class="col-md-4">
                    <label for="modalidade" class="form-label">Modalidade</label>
                    <select id="modalidade" name="modalidade" class="form-select @error('modalidade') is-invalid @enderror">
                        <option value="">Selecione...</option>
                        <option value="Presencial" @selected(old('modalidade', $evento->modalidade)=="Presencial")>Presencial</option>
                        <option value="Online" @selected(old('modalidade', $evento->modalidade)=="Online")>Online</option>
                        <option value="Híbrido" @selected(old('modalidade', $evento->modalidade)=="Híbrido")>Híbrido</option>
                    </select>
                    @error('modalidade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Duração (dias) --}}
                <div class="col-md-4">
                    <label for="duracao" class="form-label">Duração (dias)</label>
                    <input id="duracao" name="duracao" type="number" min="0" step="1"
                           value="{{ old('duracao', $evento->duracao) }}"
                           class="form-control @error('duracao') is-invalid @enderror">
                    @error('duracao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Data/hora --}}
                <div class="col-md-6">
                    <label for="data_horario" class="form-label">Data e horário</label>
                    <input id="data_horario" name="data_horario" type="datetime-local"
                           value="{{ old('data_horario', optional($evento->data_horario ? \Carbon\Carbon::parse($evento->data_horario) : null)?->format('Y-m-d\TH:i')) }}"
                           class="form-control @error('data_horario') is-invalid @enderror">
                    @error('data_horario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Local --}}
                <div class="col-md-6">
                    <label for="local" class="form-label">Local</label>
                    <input id="local" name="local" type="text"
                           value="{{ old('local', $evento->local) }}"
                           class="form-control @error('local') is-invalid @enderror"
                           placeholder="Auditório Central / Endereço / Campus / etc.">
                    @error('local') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Link --}}
                <div class="col-md-6">
                    <label for="link" class="form-label">Link (se online)</label>
                    <input id="link" name="link" type="url"
                           value="{{ old('link', $evento->link) }}"
                           class="form-control @error('link') is-invalid @enderror" placeholder="https://...">
                    @error('link') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Imagem atual (preview) + troca --}}
                <div class="col-md-6">
                    <label class="form-label d-block">Imagem da ação pedagógica</label>

                    {{-- preview da imagem atual, se existir --}}
                    @if ($evento->imagem)
                        <img src="{{ asset('storage/'.$evento->imagem) }}"
                             alt="Imagem atual"
                             class="img-fluid rounded mb-2"
                             style="max-height: 160px">
                    @else
                        <img src="{{ asset('images/logo-aeb.png') }}"
                             alt="Sem imagem"
                             class="img-fluid rounded mb-2"
                             style="max-height: 160px">
                    @endif

                    <input id="imagem" name="imagem" type="file"
                           class="form-control @error('imagem') is-invalid @enderror"
                           accept="image/*">
                    <div class="form-text">Deixe em branco para manter a atual. Máx. 2MB (JPG, PNG, WEBP, AVIF, SVG).</div>
                    @error('imagem') <div class="invalid-feedback">{{ $message }}</div> @enderror

                    {{-- Pré-visualização da nova imagem --}}
                    <img id="preview-imagem" class="mt-2 img-fluid d-none rounded" alt="Pré-visualização" style="max-height: 160px">
                </div>

                {{-- Objetivo --}}
                <div class="col-12">
                    <label for="objetivo" class="form-label">Objetivo</label>
                    <textarea id="objetivo" name="objetivo" rows="3"
                              class="form-control @error('objetivo') is-invalid @enderror">{{ old('objetivo', $evento->objetivo) }}</textarea>
                    @error('objetivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Resumo --}}
                <div class="col-12">
                    <label for="resumo" class="form-label">Resumo</label>
                    <textarea id="resumo" name="resumo" rows="3"
                              class="form-control @error('resumo') is-invalid @enderror">{{ old('resumo', $evento->resumo) }}</textarea>
                    @error('resumo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-engaja">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- preview da nova imagem (JS leve) --}}
<script>
  document.getElementById('imagem')?.addEventListener('change', function (e) {
      const file = e.target.files?.[0];
      const img  = document.getElementById('preview-imagem');
      if (file && img) {
          img.src = URL.createObjectURL(file);
          img.classList.remove('d-none');
      }
  });
</script>
@endsection
