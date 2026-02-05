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
      <input type="hidden" name="layout_frente[canvas_w]" id="layout_frente_canvas_w" value="{{ old('layout_frente.canvas_w', $modelo->layout_frente['canvas_w'] ?? '') }}">
      <input type="hidden" name="layout_frente[canvas_h]" id="layout_frente_canvas_h" value="{{ old('layout_frente.canvas_h', $modelo->layout_frente['canvas_h'] ?? '') }}">
      <input type="hidden" name="layout_frente[font_family]" id="layout_frente_font_family" value="{{ old('layout_frente.font_family', $modelo->layout_frente['font_family'] ?? 'Arial') }}">
      <input type="hidden" name="layout_frente[font_size]" id="layout_frente_font_size" value="{{ old('layout_frente.font_size', $modelo->layout_frente['font_size'] ?? 22) }}">
      <input type="hidden" name="layout_frente[font_weight]" id="layout_frente_font_weight" value="{{ old('layout_frente.font_weight', $modelo->layout_frente['font_weight'] ?? 'normal') }}">
      <input type="hidden" name="layout_frente[font_style]" id="layout_frente_font_style" value="{{ old('layout_frente.font_style', $modelo->layout_frente['font_style'] ?? 'normal') }}">
      <input type="hidden" name="layout_frente[align]" id="layout_frente_align" value="{{ old('layout_frente.align', $modelo->layout_frente['align'] ?? 'left') }}">
      <input type="hidden" name="layout_frente[styles]" id="layout_frente_styles" value="{{ old('layout_frente.styles', isset($modelo->layout_frente['styles']) ? json_encode($modelo->layout_frente['styles']) : '') }}">
      <input type="hidden" name="layout_frente[qr_x]" id="layout_frente_qr_x" value="{{ old('layout_frente.qr_x', $modelo->layout_frente['qr_x'] ?? '') }}">
      <input type="hidden" name="layout_frente[qr_y]" id="layout_frente_qr_y" value="{{ old('layout_frente.qr_y', $modelo->layout_frente['qr_y'] ?? '') }}">
      <input type="hidden" name="layout_frente[qr_size]" id="layout_frente_qr_size" value="{{ old('layout_frente.qr_size', $modelo->layout_frente['qr_size'] ?? 140) }}">

      <input type="hidden" name="layout_verso[x]" id="layout_verso_x" value="{{ old('layout_verso.x', $modelo->layout_verso['x'] ?? '') }}">
      <input type="hidden" name="layout_verso[y]" id="layout_verso_y" value="{{ old('layout_verso.y', $modelo->layout_verso['y'] ?? '') }}">
      <input type="hidden" name="layout_verso[w]" id="layout_verso_w" value="{{ old('layout_verso.w', $modelo->layout_verso['w'] ?? '') }}">
      <input type="hidden" name="layout_verso[h]" id="layout_verso_h" value="{{ old('layout_verso.h', $modelo->layout_verso['h'] ?? '') }}">
      <input type="hidden" name="layout_verso[canvas_w]" id="layout_verso_canvas_w" value="{{ old('layout_verso.canvas_w', $modelo->layout_verso['canvas_w'] ?? '') }}">
      <input type="hidden" name="layout_verso[canvas_h]" id="layout_verso_canvas_h" value="{{ old('layout_verso.canvas_h', $modelo->layout_verso['canvas_h'] ?? '') }}">
      <input type="hidden" name="layout_verso[font_family]" id="layout_verso_font_family" value="{{ old('layout_verso.font_family', $modelo->layout_verso['font_family'] ?? 'Arial') }}">
      <input type="hidden" name="layout_verso[font_size]" id="layout_verso_font_size" value="{{ old('layout_verso.font_size', $modelo->layout_verso['font_size'] ?? 22) }}">
      <input type="hidden" name="layout_verso[font_weight]" id="layout_verso_font_weight" value="{{ old('layout_verso.font_weight', $modelo->layout_verso['font_weight'] ?? 'normal') }}">
      <input type="hidden" name="layout_verso[font_style]" id="layout_verso_font_style" value="{{ old('layout_verso.font_style', $modelo->layout_verso['font_style'] ?? 'normal') }}">
      <input type="hidden" name="layout_verso[align]" id="layout_verso_align" value="{{ old('layout_verso.align', $modelo->layout_verso['align'] ?? 'left') }}">
      <input type="hidden" name="layout_verso[styles]" id="layout_verso_styles" value="{{ old('layout_verso.styles', isset($modelo->layout_verso['styles']) ? json_encode($modelo->layout_verso['styles']) : '') }}">
      <input type="hidden" name="layout_verso[qr_x]" id="layout_verso_qr_x" value="{{ old('layout_verso.qr_x', $modelo->layout_verso['qr_x'] ?? '') }}">
      <input type="hidden" name="layout_verso[qr_y]" id="layout_verso_qr_y" value="{{ old('layout_verso.qr_y', $modelo->layout_verso['qr_y'] ?? '') }}">
      <input type="hidden" name="layout_verso[qr_size]" id="layout_verso_qr_size" value="{{ old('layout_verso.qr_size', $modelo->layout_verso['qr_size'] ?? 140) }}">
      <div class="col-md-4">
        <label class="form-label" for="layout_verso_qr_color_input">Cor do QR (verso)</label>
        <input type="color"
          id="layout_verso_qr_color_input"
          name="layout_verso[qr_color]"
          class="form-control form-control-color"
          value="{{ old('layout_verso.qr_color', $modelo->layout_verso['qr_color'] ?? '#811283') }}"
          title="Cor principal do QR">
      </div>

      <div class="col-12">
        <label class="form-label">Pré-visualização - Frente</label>
        <div class="border rounded p-2 position-relative d-flex justify-content-center" style="min-height: 420px; background:#fff;">
          <div class="position-absolute top-0 start-0 end-0 d-flex align-items-center gap-2 p-2" style="z-index:2; pointer-events: auto;">
            <select id="front_toolbar_font" class="form-select form-select-sm w-auto">
              @foreach(['Arial','Georgia','Times New Roman','Courier New','Verdana','Tahoma'] as $fam)
                <option value="{{ $fam }}">{{ $fam }}</option>
              @endforeach
            </select>
            <input type="number" min="8" max="96" id="front_toolbar_size" class="form-control form-control-sm w-auto" value="22">
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-outline-secondary" data-front-style="bold" title="Negrito (seleção ou bloco)">B</button>
              <button type="button" class="btn btn-outline-secondary" data-front-style="italic" title="Itálico (seleção ou bloco)"><em>I</em></button>
            </div>
            <div class="btn-group btn-group-sm" role="group">
              @foreach(['left'=>'⭠ Esq','center'=>'⭣ Centro','right'=>'⭢ Dir','justify'=>'☰ Just'] as $k=>$label)
                <button type="button" class="btn btn-outline-secondary" data-front-align="{{ $k }}" title="Alinhar {{ ['left'=>'à esquerda','center'=>'ao centro','right'=>'à direita','justify'=>'justificado'][$k] }}">{{ $label }}</button>
              @endforeach
            </div>
          </div>
          <canvas id="canvas-frente" class="d-block" style="margin:0 auto;"></canvas>
        </div>
        <div class="form-text">Arraste o texto para posicionar; a posição será salva.</div>
      </div>

      <div class="col-12">
        <label class="form-label">Pré-visualização - Verso</label>
        <div class="border rounded p-2 position-relative d-flex justify-content-center" style="min-height: 420px; background:#fff;">
          <div class="position-absolute top-0 start-0 end-0 d-flex align-items-center gap-2 p-2" style="z-index:2; pointer-events: auto;">
            <select id="back_toolbar_font" class="form-select form-select-sm w-auto">
              @foreach(['Arial','Georgia','Times New Roman','Courier New','Verdana','Tahoma'] as $fam)
                <option value="{{ $fam }}">{{ $fam }}</option>
              @endforeach
            </select>
            <input type="number" min="8" max="96" id="back_toolbar_size" class="form-control form-control-sm w-auto" value="22">
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-outline-secondary" data-back-style="bold" title="Negrito (seleção ou bloco)">B</button>
              <button type="button" class="btn btn-outline-secondary" data-back-style="italic" title="Itálico (seleção ou bloco)"><em>I</em></button>
            </div>
            <div class="btn-group btn-group-sm" role="group">
              @foreach(['left'=>'⭠ Esq','center'=>'⭣ Centro','right'=>'⭢ Dir','justify'=>'☰ Just'] as $k=>$label)
                <button type="button" class="btn btn-outline-secondary" data-back-align="{{ $k }}" title="Alinhar {{ ['left'=>'à esquerda','center'=>'ao centro','right'=>'à direita','justify'=>'justificado'][$k] }}">{{ $label }}</button>
              @endforeach
            </div>
          </div>
          <canvas id="canvas-verso" class="d-block" style="margin:0 auto;"></canvas>
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
{{-- Carrega Fabric somente nesta tela de modelo de certificado --}}
<script src="https://unpkg.com/fabric@5.3.0/dist/fabric.min.js"></script>
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

  function initFabricPreview(opts) {
    const {
      canvasId, fileInputId, textareaId,
      xInputId, yInputId, wInputId, hInputId,
      canvasWInputId, canvasHInputId,
      fontFamilyInputId, fontSizeInputId, fontWeightInputId, fontStyleInputId, alignInputId, stylesInputId,
      qrXInputId, qrYInputId, qrSizeInputId,
      existingUrl, toolbar,
      qrEnabled = true,
    } = opts;

    const canvasEl = document.getElementById(canvasId);
    const container = canvasEl?.parentElement;
    const fileInput = document.getElementById(fileInputId);
    const textArea = document.getElementById(textareaId);
    const xInput = document.getElementById(xInputId);
    const yInput = document.getElementById(yInputId);
    const wInput = document.getElementById(wInputId);
    const hInput = document.getElementById(hInputId);
    const canvasWInput = document.getElementById(canvasWInputId);
    const canvasHInput = document.getElementById(canvasHInputId);
    const fontFamilyInput = document.getElementById(fontFamilyInputId);
    const fontSizeInput = document.getElementById(fontSizeInputId);
    const fontWeightInput = document.getElementById(fontWeightInputId);
    const fontStyleInput = document.getElementById(fontStyleInputId);
    const alignInput = document.getElementById(alignInputId);
    const stylesInput = document.getElementById(stylesInputId);
    const qrXInput = qrEnabled ? document.getElementById(qrXInputId) : null;
    const qrYInput = qrEnabled ? document.getElementById(qrYInputId) : null;
    const qrSizeInput = qrEnabled ? document.getElementById(qrSizeInputId) : null;
    if (!canvasEl || !fileInput || !textArea || !xInput || !yInput || !wInput || !hInput || !fontFamilyInput || !fontSizeInput || !fontWeightInput || !fontStyleInput || !alignInput || !stylesInput) return;

    const canvas = new fabric.Canvas(canvasId, { selection: false, backgroundColor: '#ffffff' });
    let textObj = null;
    let qrObj = null;
    let guides = [];
    let snapGuides = [];

    const drawGuides = () => {
      guides.forEach(g => canvas.remove(g));
      snapGuides.forEach(g => canvas.remove(g));
      guides = [];
      snapGuides = [];
      const w = canvas.getWidth();
      const h = canvas.getHeight();
      const g = [
        new fabric.Line([w / 2, 0, w / 2, h], { stroke: '#cbd5e1', strokeDashArray: [5, 5], selectable: false, evented: false }),
        new fabric.Line([0, h / 2, w, h / 2], { stroke: '#cbd5e1', strokeDashArray: [5, 5], selectable: false, evented: false }),
        new fabric.Rect({ left: w * 0.05, top: h * 0.05, width: w * 0.9, height: h * 0.9, stroke: '#e2e8f0', strokeDashArray: [4, 6], fill: 'rgba(0,0,0,0)', selectable: false, evented: false })
      ];
      g.forEach(line => canvas.add(line));
      guides = g;
    };

    const setInitialSize = () => {
      const w = container?.clientWidth || 960;
      const h = 400;
      canvas.setWidth(w);
      canvas.setHeight(h);
      drawGuides();
      canvas.renderAll();
    };
    setInitialSize();

    const updateHidden = () => {
      if (!textObj) return;
      xInput.value = Math.round(textObj.left ?? 0);
      yInput.value = Math.round(textObj.top ?? 0);
      wInput.value = Math.round(textObj.width ?? 0);
      hInput.value = Math.round(textObj.height ?? 0);
      if (canvasWInput) canvasWInput.value = Math.round(canvas.getWidth());
      if (canvasHInput) canvasHInput.value = Math.round(canvas.getHeight());
      fontFamilyInput.value = textObj.fontFamily || 'Arial';
      fontSizeInput.value = Math.round(textObj.fontSize || 22);
      fontWeightInput.value = textObj.fontWeight || 'normal';
      fontStyleInput.value = textObj.fontStyle || 'normal';
      alignInput.value = textObj.textAlign || 'left';
      stylesInput.value = JSON.stringify(textObj.styles || {});
      if (qrObj) {
        qrXInput && (qrXInput.value = Math.round(qrObj.left ?? 0));
        qrYInput && (qrYInput.value = Math.round(qrObj.top ?? 0));
        qrSizeInput && (qrSizeInput.value = Math.round(qrObj.width ?? 0));
      }
    };

    const ensureText = () => {
      if (textObj) {
        textObj.text = textArea.value || '';
        canvas.renderAll();
        return;
      }
      let presetStyles = {};
      try { if (stylesInput.value) presetStyles = JSON.parse(stylesInput.value); } catch (e) {}

      textObj = new fabric.Textbox(textArea.value || 'Texto', {
        left: parseFloat(xInput.value) || 40,
        top: parseFloat(yInput.value) || 40,
        width: parseFloat(wInput.value) || 300,
        height: parseFloat(hInput.value) || undefined,
        fill: '#111',
        fontSize: parseFloat(fontSizeInput.value) || 22,
        fontFamily: fontFamilyInput.value || 'Arial',
        fontWeight: fontWeightInput.value || 'normal',
        fontStyle: fontStyleInput.value || 'normal',
        textAlign: alignInput.value || 'left',
        editable: true,
        lockScalingFlip: true,
        styles: presetStyles,
      });

      const showSnap = () => {
        snapGuides.forEach(g => canvas.remove(g));
        snapGuides = [];
        const w = canvas.getWidth();
        const h = canvas.getHeight();
        const threshold = 5;
        const cx = textObj.left + (textObj.width || 0) / 2;
        const cy = textObj.top + (textObj.height || 0) / 2;
        if (Math.abs(cx - w / 2) <= threshold) {
          snapGuides.push(new fabric.Line([w / 2, 0, w / 2, h], { stroke: '#f97316', selectable: false, evented: false }));
        }
        if (Math.abs(cy - h / 2) <= threshold) {
          snapGuides.push(new fabric.Line([0, h / 2, w, h / 2], { stroke: '#f97316', selectable: false, evented: false }));
        }
        snapGuides.forEach(g => canvas.add(g));
        canvas.renderAll();
      };

      const lockScale = () => {
        const newW = (textObj.width || 0) * (textObj.scaleX || 1);
        const newH = (textObj.height || 0) * (textObj.scaleY || 1);
        textObj.set({ width: newW, height: newH, scaleX: 1, scaleY: 1 });
      };
      textObj.on('modified', () => { lockScale(); updateHidden(); showSnap(); });
      textObj.on('moving', () => { updateHidden(); showSnap(); });
      textObj.on('scaled', () => { lockScale(); updateHidden(); showSnap(); });
      textObj.on('scaling', () => { lockScale(); updateHidden(); showSnap(); });
      textObj.on('changed', updateHidden);
      textObj.on('selection:changed', () => {
        syncToolbarWithSelection();
      });
      canvas.add(textObj);
      canvas.renderAll();
      syncToolbarWithSelection();
    };

    const ensureQr = () => {
      if (!qrEnabled || qrObj || !qrXInput || !qrYInput || !qrSizeInput) return;
      const qx = parseFloat(qrXInput.value || '0') || canvas.getWidth() * 0.78;
      const qy = parseFloat(qrYInput.value || '0') || canvas.getHeight() * 0.75;
      const qs = parseFloat(qrSizeInput.value || '140') || 140;
      qrObj = new fabric.Rect({
        left: qx,
        top: qy,
        width: qs,
        height: qs,
        fill: 'rgba(34,197,94,0.18)',
        stroke: '#16a34a',
        strokeWidth: 3,
        selectable: true,
        evented: true,
        lockRotation: true,
        lockScalingFlip: true,
      });
      qrObj.on('modified', () => {
        qrObj.set({ scaleX: 1, scaleY: 1 });
        updateHidden();
      });
      qrObj.on('moving', updateHidden);
      qrObj.on('scaled', () => {
        qrObj.set({ width: qrObj.width * qrObj.scaleX, height: qrObj.height * qrObj.scaleY, scaleX: 1, scaleY: 1 });
        updateHidden();
      });
      qrObj.on('scaling', () => {
        qrObj.set({ width: qrObj.width * qrObj.scaleX, height: qrObj.height * qrObj.scaleY, scaleX: 1, scaleY: 1 });
        updateHidden();
      });
      canvas.add(qrObj);
      qrObj.bringToFront();
      textObj && textObj.bringToFront();
      updateHidden();
      canvas.renderAll();
    };

    const loadImage = (url) => {
      // Sempre desenha guias e texto, mesmo que não haja imagem
      drawGuides();
      ensureText();
      ensureQr();

      const fallbackSize = () => {
        const targetW = (container?.clientWidth ?? 960) - 24;
        const targetH = Math.round(targetW * 0.6);
        canvas.setWidth(targetW);
        canvas.setHeight(targetH);
        canvasEl.style.width = `${targetW}px`;
        canvasEl.style.height = `${targetH}px`;
        if (container) container.style.height = `${targetH}px`;
      };

      if (!url) {
        fallbackSize();
        ensureQr();
        canvas.renderAll();
        return;
      }

      const absoluteUrl = url.startsWith('http') ? url : `${window.location.origin}${url.startsWith('/') ? '' : '/'}${url}`;
      const imgEl = new Image();
      imgEl.crossOrigin = 'anonymous';
      imgEl.onload = () => {
        const targetW = (container?.clientWidth ?? 960) - 24;
        const scale = Math.min(targetW / imgEl.naturalWidth, 1);
        const imgW = imgEl.naturalWidth * scale;
        const imgH = imgEl.naturalHeight * scale;

        canvas.setWidth(imgW);
        canvas.setHeight(imgH);
        canvasEl.style.width = `${imgW}px`;
        canvasEl.style.height = `${imgH}px`;
        if (canvasWInput) canvasWInput.value = Math.round(imgW);
        if (canvasHInput) canvasHInput.value = Math.round(imgH);
        if (container) container.style.height = `${imgH}px`;

        const fabricImg = new fabric.Image(imgEl, {
          originX: 'left',
          originY: 'top',
          left: 0,
          top: 0,
          selectable: false,
          evented: false,
          scaleX: scale,
          scaleY: scale,
        });

        canvas.setBackgroundImage(fabricImg, canvas.renderAll.bind(canvas), {
          originX: 'left',
          originY: 'top',
          left: 0,
          top: 0,
        });
        drawGuides();
        if (textObj) {
          textObj.text = textArea.value || 'Texto';
        }
        ensureQr();
        canvas.renderAll();
      };
      imgEl.onerror = () => {
        fallbackSize();
        drawGuides();
        ensureText();
        ensureQr();
        canvas.renderAll();
      };
      imgEl.src = absoluteUrl;
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

    const currentSelectionStyle = () => {
      if (!textObj) return {};
      const styles = textObj.getSelectionStyles(textObj.selectionStart, textObj.selectionEnd);
      if (styles && styles.length) return styles[0];
      return {
        fontFamily: textObj.fontFamily,
        fontSize: textObj.fontSize,
        fontWeight: textObj.fontWeight,
        fontStyle: textObj.fontStyle,
        textAlign: textObj.textAlign,
      };
    };

    const applyStyle = (selectionOnly = false, overrides = {}) => {
      if (!textObj) return;
      const base = currentSelectionStyle();
      const stylePayload = {
        fontFamily: fontFamilyInput.value || base.fontFamily || 'Arial',
        fontSize: parseFloat(fontSizeInput.value) || base.fontSize || 22,
        fontWeight: fontWeightInput.value || base.fontWeight || 'normal',
        fontStyle: fontStyleInput.value || base.fontStyle || 'normal',
        textAlign: alignInput.value || base.textAlign || 'left',
        ...overrides,
      };

      const isAlignChange = overrides.textAlign !== undefined;
      if (isAlignChange) {
        textObj.set('textAlign', overrides.textAlign);
        alignInput.value = overrides.textAlign;
      }

      if (selectionOnly && textObj.selectionStart !== textObj.selectionEnd && !isAlignChange) {
        textObj.setSelectionStyles(stylePayload, textObj.selectionStart, textObj.selectionEnd);
        textObj.set(stylePayload);
      } else {
        textObj.set(stylePayload);
      }
      updateHidden();
      canvas.renderAll();
    };

    const syncToolbarWithSelection = () => {
      const s = currentSelectionStyle();
      fontFamilyInput.value = s.fontFamily || 'Arial';
      fontSizeInput.value = Math.round(s.fontSize || 22);
      fontWeightInput.value = s.fontWeight || 'normal';
      fontStyleInput.value = s.fontStyle || 'normal';
      alignInput.value = s.textAlign || 'left';
      updateHidden();

      if (toolbar) {
        const { fontFamilySelect, fontSizeField, boldBtn, italicBtn, alignButtons } = toolbar;
        if (fontFamilySelect) fontFamilySelect.value = fontFamilyInput.value;
        if (fontSizeField) fontSizeField.value = fontSizeInput.value;
        if (boldBtn) boldBtn.classList.toggle('active', fontWeightInput.value === 'bold');
        if (italicBtn) italicBtn.classList.toggle('active', fontStyleInput.value === 'italic');
        alignButtons?.forEach(btn => btn.classList.toggle('active', btn.dataset.align === alignInput.value));
      }
    };

    if (toolbar) {
      const { fontFamilySelect, fontSizeField, boldBtn, italicBtn, alignButtons } = toolbar;
      if (fontFamilySelect) {
        fontFamilySelect.addEventListener('change', () => {
          fontFamilyInput.value = fontFamilySelect.value;
          applyStyle(true);
        });
      }
      if (fontSizeField) {
        fontSizeField.addEventListener('input', () => {
          fontSizeInput.value = fontSizeField.value;
          applyStyle(true);
        });
      }
      if (boldBtn) {
        boldBtn.addEventListener('click', () => {
          const next = (currentSelectionStyle().fontWeight === 'bold') ? 'normal' : 'bold';
          fontWeightInput.value = next;
          boldBtn.classList.toggle('active', next === 'bold');
          applyStyle(true, { fontWeight: next });
        });
      }
      if (italicBtn) {
        italicBtn.addEventListener('click', () => {
          const next = (currentSelectionStyle().fontStyle === 'italic') ? 'normal' : 'italic';
          fontStyleInput.value = next;
          italicBtn.classList.toggle('active', next === 'italic');
          applyStyle(true, { fontStyle: next });
        });
      }
      if (alignButtons && alignButtons.length) {
        alignButtons.forEach(btn => {
          const rawAlign = btn.dataset.align || btn.dataset.frontAlign || btn.dataset.backAlign;
          btn.dataset.align = rawAlign || 'left';
        });
        alignButtons.forEach(btn => {
          btn.addEventListener('click', () => {
            alignButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const newAlign = btn.dataset.align || 'left';
            alignInput.value = newAlign;
            applyStyle(false, { textAlign: newAlign });
          });
        });
      }
    }

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
        canvasWInputId: 'layout_frente_canvas_w',
        canvasHInputId: 'layout_frente_canvas_h',
        fontFamilyInputId: 'layout_frente_font_family',
        fontSizeInputId: 'layout_frente_font_size',
        fontWeightInputId: 'layout_frente_font_weight',
        fontStyleInputId: 'layout_frente_font_style',
        alignInputId: 'layout_frente_align',
        stylesInputId: 'layout_frente_styles',
        toolbar: {
          fontFamilySelect: document.getElementById('front_toolbar_font'),
          fontSizeField: document.getElementById('front_toolbar_size'),
          boldBtn: document.querySelector('[data-front-style="bold"]'),
          italicBtn: document.querySelector('[data-front-style="italic"]'),
          alignButtons: Array.from(document.querySelectorAll('[data-front-align]')),
        },
        qrEnabled: false,
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
        canvasWInputId: 'layout_verso_canvas_w',
        canvasHInputId: 'layout_verso_canvas_h',
        fontFamilyInputId: 'layout_verso_font_family',
        fontSizeInputId: 'layout_verso_font_size',
        fontWeightInputId: 'layout_verso_font_weight',
        fontStyleInputId: 'layout_verso_font_style',
        alignInputId: 'layout_verso_align',
        stylesInputId: 'layout_verso_styles',
        qrXInputId: 'layout_verso_qr_x',
        qrYInputId: 'layout_verso_qr_y',
        qrSizeInputId: 'layout_verso_qr_size',
        toolbar: {
          fontFamilySelect: document.getElementById('back_toolbar_font'),
          fontSizeField: document.getElementById('back_toolbar_size'),
          boldBtn: document.querySelector('[data-back-style="bold"]'),
          italicBtn: document.querySelector('[data-back-style="italic"]'),
          alignButtons: Array.from(document.querySelectorAll('[data-back-align]')),
        },
        existingUrl: "{{ !empty($modelo?->imagem_verso) ? asset('storage/'.$modelo->imagem_verso) : '' }}",
      });
    });
  });
</script>
@endpush
