<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Certificado</title>
  <style>
    :root { --page-width: 1120px; }
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f7f7f7;
    }
    .wrapper {
      max-width: var(--page-width);
      margin: 20px auto;
      padding: 12px;
    }
    .card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      padding: 12px;
    }
    .cert-page {
      position: relative;
      width: 100%;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      overflow: hidden;
      margin-bottom: 24px;
      page-break-after: always;
    }
    .cert-bg {
      width: 100%;
      height: auto;
      display: block;
    }
    .cert-text { position: absolute; color: #111; line-height: 1.4; white-space: pre-line; text-align: justify; text-align-last: left; text-justify: inter-word; }
    .actions {
      display: flex;
      gap: 10px;
      margin-bottom: 12px;
    }
    .btn {
      padding: 10px 14px;
      border: 1px solid #6c2a6a;
      background: #6c2a6a;
      color: #fff;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
    }
    .btn-outline { background: #fff; color: #6c2a6a; }

    @media print {
      body { background: #fff; }
      .wrapper { margin: 0; padding: 0; max-width: 100%; }
      .actions { display: none !important; }
      .card { border: none; box-shadow: none; padding: 0; }
      @page { size: A4 landscape; margin: 10mm; }
      .cert-page { page-break-after: always; border: none; border-radius: 0; margin: 0 0 10mm 0; }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="actions">
      <a href="{{ route('certificados.download', $certificado) }}" class="btn">Baixar PDF</a>
      <a href="{{ url()->previous() }}" class="btn btn-outline">Voltar</a>
    </div>

    <div class="card">
      @php
        $modelo = $certificado->modelo;
        $frenteUrl = $modelo?->imagem_frente ? asset('storage/'.$modelo->imagem_frente) : '';
        $versoUrl = $modelo?->imagem_verso ? asset('storage/'.$modelo->imagem_verso) : '';
        $layoutFrente = $modelo->layout_frente ?? [];
        $layoutVerso  = $modelo->layout_verso ?? [];
        $textoFrente = trim($certificado->texto_frente ?? '');
        $textoVerso  = trim($certificado->texto_verso ?? '');
      @endphp

      <div class="cert-page">
        @if($frenteUrl)
          <img src="{{ $frenteUrl }}" alt="Frente" class="cert-bg">
        @endif
        <div class="cert-text"
             data-cert-layer="frente"
             data-layout='@json($layoutFrente)'
             data-text="{!! nl2br(e($textoFrente)) !!}">
          {!! $renderStyled($textoFrente, $layoutFrente['styles'] ?? [], $layoutFrente['font_weight'] ?? 'normal', $layoutFrente['font_style'] ?? 'normal') !!}
        </div>
      </div>

      @if($versoUrl || $textoVerso)
      <div class="cert-page">
        @if($versoUrl)
          <img src="{{ $versoUrl }}" alt="Verso" class="cert-bg">
        @endif
        <div class="cert-text"
             data-cert-layer="verso"
             data-layout='@json($layoutVerso)'
             data-text="{!! nl2br(e($textoVerso)) !!}">
          {!! $renderStyled($textoVerso, $layoutVerso['styles'] ?? [], $layoutVerso['font_weight'] ?? 'normal', $layoutVerso['font_style'] ?? 'normal') !!}
        </div>
      </div>
      @endif
    </div>
  </div>

  <script>
    function applyLayout(layer) {
      const page = layer.closest('.cert-page');
      const img = page ? page.querySelector('.cert-bg') : null;
      const layout = layer.dataset.layout ? JSON.parse(layer.dataset.layout) : {};
      const apply = () => {
        const cw = Number(layout.canvas_w || img?.naturalWidth || img?.clientWidth || 1);
        const ch = Number(layout.canvas_h || img?.naturalHeight || img?.clientHeight || 1);
        const scaleW = (img?.clientWidth || cw) / cw;
        const scaleH = (img?.clientHeight || ch) / ch;
        const scale = Math.min(scaleW || 1, scaleH || 1);
        const x = (layout.x || 0) * scale;
        const y = (layout.y || 0) * scale;
        const w = (layout.w || 0) * scale;
        const h = (layout.h || 0) * scale;
        const fs = (layout.font_size || 20) * scale;
        const ff = layout.font_family || 'Arial';
        const fw = layout.font_weight || 'normal';
        const fst = layout.font_style || 'normal';
        const align = layout.align || 'left';

        layer.style.left = `${x}px`;
        layer.style.top = `${y}px`;
        layer.style.fontSize = `${fs}px`;
        layer.style.fontFamily = ff;
        layer.style.fontWeight = fw;
        layer.style.fontStyle = fst;
        layer.style.textAlign = align;
        layer.style.width = w > 0 ? `${w}px` : `${img?.clientWidth || cw}px`;
        layer.style.height = h > 0 ? `${h}px` : 'auto';
      };
      if (img) {
        if (img.complete) apply(); else img.onload = apply;
      } else {
        apply();
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('[data-cert-layer]').forEach(layer => applyLayout(layer));
    });
  </script>
</body>
</html>
