<div class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" for="nome">Nome*</label>
        <input type="text" id="nome" name="nome" class="form-control @error('nome') is-invalid @enderror"
          value="{{ old('nome', $modelo->nome ?? '') }}" required>
        @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label" for="eixo_id">Eixo</label>
        <select id="eixo_id" name="eixo_id" class="form-select @error('eixo_id') is-invalid @enderror">
          <option value="">-- Nenhum --</option>
          @foreach($eixos as $id => $nomeEixo)
            <option value="{{ $id }}" @selected(old('eixo_id', $modelo->eixo_id ?? '') == $id)>{{ $nomeEixo }}</option>
          @endforeach
        </select>
        @error('eixo_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-12">
        <label class="form-label" for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao" rows="2" class="form-control @error('descricao') is-invalid @enderror">{{ old('descricao', $modelo->descricao ?? '') }}</textarea>
        @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label" for="imagem_frente">Imagem da frente</label>
        <input type="file" id="imagem_frente" name="imagem_frente" accept="image/*"
          class="form-control @error('imagem_frente') is-invalid @enderror">
        <div class="form-text">Envie uma imagem; salvaremos o caminho no disco.</div>
        <div class="mt-2" id="preview-frente-wrapper">
          @if(!empty($modelo?->imagem_frente))
            <img src="{{ asset('storage/'.$modelo->imagem_frente) }}" alt="Imagem atual da frente" class="img-fluid border rounded" style="max-height: 180px;">
          @endif
        </div>
        @error('imagem_frente') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label" for="imagem_verso">Imagem do verso</label>
        <input type="file" id="imagem_verso" name="imagem_verso" accept="image/*"
          class="form-control @error('imagem_verso') is-invalid @enderror">
        <div class="form-text">Opcional; se não enviar, manterá a imagem atual.</div>
        <div class="mt-2" id="preview-verso-wrapper">
          @if(!empty($modelo?->imagem_verso))
            <img src="{{ asset('storage/'.$modelo->imagem_verso) }}" alt="Imagem atual do verso" class="img-fluid border rounded" style="max-height: 180px;">
          @endif
        </div>
        @error('imagem_verso') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-12">
        <label class="form-label" for="texto_frente">Texto da frente</label>
        <textarea id="texto_frente" name="texto_frente" rows="4" class="form-control @error('texto_frente') is-invalid @enderror">{{ old('texto_frente', $modelo->texto_frente ?? '') }}</textarea>
        @error('texto_frente') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-12">
        <label class="form-label" for="texto_verso">Texto do verso</label>
        <textarea id="texto_verso" name="texto_verso" rows="4" class="form-control @error('texto_verso') is-invalid @enderror">{{ old('texto_verso', $modelo->texto_verso ?? '') }}</textarea>
        @error('texto_verso') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <input type="hidden" name="layout_frente[x]" id="layout_frente_x" value="{{ old('layout_frente.x', $modelo->layout_frente['x'] ?? '') }}">
      <input type="hidden" name="layout_frente[y]" id="layout_frente_y" value="{{ old('layout_frente.y', $modelo->layout_frente['y'] ?? '') }}">
      <input type="hidden" name="layout_frente[w]" id="layout_frente_w" value="{{ old('layout_frente.w', $modelo->layout_frente['w'] ?? '') }}">
      <input type="hidden" name="layout_frente[h]" id="layout_frente_h" value="{{ old('layout_frente.h', $modelo->layout_frente['h'] ?? '') }}">
      <input type="hidden" name="layout_verso[x]" id="layout_verso_x" value="{{ old('layout_verso.x', $modelo->layout_verso['x'] ?? '') }}">
      <input type="hidden" name="layout_verso[y]" id="layout_verso_y" value="{{ old('layout_verso.y', $modelo->layout_verso['y'] ?? '') }}">
      <input type="hidden" name="layout_verso[w]" id="layout_verso_w" value="{{ old('layout_verso.w', $modelo->layout_verso['w'] ?? '') }}">
      <input type="hidden" name="layout_verso[h]" id="layout_verso_h" value="{{ old('layout_verso.h', $modelo->layout_verso['h'] ?? '') }}">

      <div class="col-12">
        <label class="form-label">Pré-visualização - Frente</label>
        <div class="border rounded p-2 bg-light">
          <canvas id="canvas-frente"></canvas>
        </div>
        <div class="form-text">Arraste o texto para posicionar; a posição será salva.</div>
      </div>

      <div class="col-12">
        <label class="form-label">Pré-visualização - Verso</label>
        <div class="border rounded p-2 bg-light">
          <canvas id="canvas-verso"></canvas>
        </div>
        <div class="form-text">Arraste o texto para posicionar; a posição será salva.</div>
      </div>
    </div>
  </div>
</div>

<div class="d-flex justify-content-end">
  <button type="submit" class="btn btn-engaja">Salvar modelo</button>
</div>

@push('scripts')
<script>
  function simplePreview(inputId, previewWrapperId) {
    const input = document.getElementById(inputId);
    const wrap = document.getElementById(previewWrapperId);
    if (!input || !wrap) return;
    input.addEventListener('change', e => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      const url = URL.createObjectURL(file);
      wrap.innerHTML = `<img src="${url}" alt="Pré-visualização" class="img-fluid border rounded" style="max-height: 180px;">`;
    });
  }
  simplePreview('imagem_frente', 'preview-frente-wrapper');
  simplePreview('imagem_verso', 'preview-verso-wrapper');

  function ensureFabric(cb) {
    if (window.fabric) {
      cb();
      return;
    }
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/fabric@5.3.0/dist/fabric.min.js';
    script.onload = cb;
    document.head.appendChild(script);
  }

  function initFabricPreview({ canvasId, fileInputId, textareaId, xInputId, yInputId, wInputId, hInputId, existingUrl }) {
    const canvasEl = document.getElementById(canvasId);
    const container = canvasEl?.parentElement;
    const fileInput = document.getElementById(fileInputId);
    const textArea = document.getElementById(textareaId);
    const xInput = document.getElementById(xInputId);
    const yInput = document.getElementById(yInputId);
    const wInput = document.getElementById(wInputId);
    const hInput = document.getElementById(hInputId);
    if (!canvasEl || !fileInput || !textArea || !xInput || !yInput || !wInput || !hInput) return;

    const canvas = new fabric.Canvas(canvasId, { selection: false, backgroundColor: '#ffffff' });
    let textObj = null;

    const setInitialSize = () => {
      const fallbackW = 960;
      const fallbackH = 400;
      const w = container?.clientWidth || fallbackW;
      canvas.setWidth(w);
      canvas.setHeight(fallbackH);
    };
    setInitialSize();

    const updateHidden = () => {
      if (!textObj) return;
      xInput.value = Math.round(textObj.left ?? 0);
      yInput.value = Math.round(textObj.top ?? 0);
      wInput.value = Math.round(textObj.getScaledWidth() ?? textObj.width ?? 0);
      hInput.value = Math.round(textObj.getScaledHeight() ?? textObj.height ?? 0);
    };

    const ensureText = () => {
      if (textObj) {
        textObj.text = textArea.value || '';
        canvas.renderAll();
        return;
      }
      textObj = new fabric.Textbox(textArea.value || 'Texto', {
        left: parseFloat(xInput.value) || 40,
        top: parseFloat(yInput.value) || 40,
        width: parseFloat(wInput.value) || 300,
        height: parseFloat(hInput.value) || undefined,
        fill: '#111',
        fontSize: 22,
        editable: true,
        lockScalingFlip: true,
      });
      textObj.on('modified', updateHidden);
      textObj.on('moving', updateHidden);
      textObj.on('scaled', updateHidden);
      textObj.on('scaling', updateHidden);
      canvas.add(textObj);
      canvas.renderAll();
    };

    const loadImage = (url) => {
      if (!url) {
        ensureText();
        canvas.renderAll();
        return;
      }
      fabric.Image.fromURL(url, (img) => {
        const maxW = 800;
        const scale = Math.min(1, maxW / img.width);
        img.scale(scale);
        canvas.setWidth(img.width * scale);
        canvas.setHeight(img.height * scale);
        canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas));
        ensureText();
      }, { crossOrigin: 'anonymous' });
    };

    textArea.addEventListener('input', () => {
      if (textObj) {
        textObj.text = textArea.value || '';
        canvas.renderAll();
      }
    });

    fileInput.addEventListener('change', e => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      const url = URL.createObjectURL(file);
      loadImage(url);
    });

    const initialUrl = existingUrl || (fileInput.files && fileInput.files[0] ? URL.createObjectURL(fileInput.files[0]) : '');
    loadImage(initialUrl);
  }

  document.addEventListener('DOMContentLoaded', () => {
    ensureFabric(() => {
      initFabricPreview({
        canvasId: 'canvas-frente',
        fileInputId: 'imagem_frente',
        textareaId: 'texto_frente',
        xInputId: 'layout_frente_x',
        yInputId: 'layout_frente_y',
        wInputId: 'layout_frente_w',
        hInputId: 'layout_frente_h',
        existingUrl: "{{ !empty($modelo?->imagem_frente) ? asset('storage/'.$modelo->imagem_frente) : '' }}",
      });
      initFabricPreview({
        canvasId: 'canvas-verso',
        fileInputId: 'imagem_verso',
        textareaId: 'texto_verso',
        xInputId: 'layout_verso_x',
        yInputId: 'layout_verso_y',
        wInputId: 'layout_verso_w',
        hInputId: 'layout_verso_h',
        existingUrl: "{{ !empty($modelo?->imagem_verso) ? asset('storage/'.$modelo->imagem_verso) : '' }}",
      });
    });
  });
</script>
@endpush
