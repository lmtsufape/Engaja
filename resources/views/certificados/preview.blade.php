<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <style>
    @page { size: A4 landscape; margin: 0; }
    body { margin: 0; padding: 0; font-family: Arial, sans-serif; background: #f7f7f7; }
    .page {
      position: relative;
      width: 100%;
      height: 100vh;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .page-inner {
      position: relative;
      width: 842pt;
      height: 595pt;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    .bg {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .text-layer {
      position: absolute;
      inset: 12%;
      color: #111;
      font-size: 20px;
      line-height: 1.4;
      white-space: normal;
      text-align: justify;
      text-align-last: left;
      text-justify: inter-word;
      margin: 0;
      padding: 0;
      word-break: normal;
    }
  </style>
</head>
<body>
  @php
    $modelo = $certificado->modelo;
    $frenteFile = $modelo?->imagem_frente ? public_path('storage/'.$modelo->imagem_frente) : null;
    $versoFile  = $modelo?->imagem_verso ? public_path('storage/'.$modelo->imagem_verso) : null;
    $layoutFrente = $modelo->layout_frente ?? [];
    $layoutVerso  = $modelo->layout_verso ?? [];
    $textoFrente = trim($certificado->texto_frente ?? '');
    $textoVerso  = trim($certificado->texto_verso ?? '');

    $renderStyled = function (string $texto, array $styles, string $baseWeight = 'normal', string $baseStyle = 'normal'): string {
        $linhas = preg_split('/\r\n|\r|\n/', $texto);
        $out = '';
        foreach ($linhas as $lineIndex => $linha) {
            $chars = preg_split('//u', $linha, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $lineStyles = $styles[$lineIndex] ?? [];
            $currentBold = false;
            $currentItalic = false;
            $buffer = '';
            $flush = function() use (&$out, &$buffer, &$currentBold, &$currentItalic) {
                if ($buffer === '') return;
                $escaped = e($buffer);
                if ($currentItalic) $escaped = '<em>'.$escaped.'</em>';
                if ($currentBold) $escaped = '<strong>'.$escaped.'</strong>';
                $out .= $escaped;
                $buffer = '';
            };
            foreach ($chars as $i => $ch) {
                $st = $lineStyles[$i] ?? [];
                $bold = ($st['fontWeight'] ?? $baseWeight) === 'bold';
                $italic = ($st['fontStyle'] ?? $baseStyle) === 'italic';
                if ($bold !== $currentBold || $italic !== $currentItalic) {
                    $flush();
                    $currentBold = $bold;
                    $currentItalic = $italic;
                }
                $buffer .= $ch;
            }
            $flush();
            if ($lineIndex < count($linhas) - 1) {
                $out .= '<br>';
            }
        }
        return $out;
    };

    $toBase64Reduced = function ($filePath, $maxWidth = 2600, $quality = 92) {
        if (! $filePath || ! file_exists($filePath)) {
            return null;
        }
        $info = getimagesize($filePath);
        if (! $info) {
            return null;
        }
        $mime = $info['mime'] ?? 'image/jpeg';
        $createFn = match ($mime) {
            'image/png'  => 'imagecreatefrompng',
            'image/gif'  => 'imagecreatefromgif',
            default      => 'imagecreatefromjpeg',
        };
        if (!function_exists($createFn)) {
            $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'png';
            $data = base64_encode(file_get_contents($filePath));
            return "data:image/{$ext};base64,{$data}";
        }
        $src = @$createFn($filePath);
        if (!$src) {
            $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'png';
            $data = base64_encode(file_get_contents($filePath));
            return "data:image/{$ext};base64,{$data}";
        }
        $origW = imagesx($src);
        $origH = imagesy($src);
        $scale = $origW > $maxWidth ? ($maxWidth / $origW) : 1;
        $newW = (int)($origW * $scale);
        $newH = (int)($origH * $scale);
        $dst = imagecreatetruecolor($newW, $newH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        ob_start();
        imagejpeg($dst, null, $quality);
        $data = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);
        return 'data:image/jpeg;base64,'.base64_encode($data);
    };

    $frenteUrl = $toBase64Reduced($frenteFile);
    $versoUrl  = $toBase64Reduced($versoFile);
  @endphp

  <div class="page">
    <div class="page-inner">
      @if($frenteUrl)
        <img src="{{ $frenteUrl }}" class="bg" alt="Frente">
      @endif
      @php
        $cw = max(1, (float)($layoutFrente['canvas_w'] ?? 2000));
        $ch = max(1, (float)($layoutFrente['canvas_h'] ?? 1100));
        $scaleW = 842.0 / $cw;
        $scaleH = 595.0 / $ch;
        $scale = min($scaleW, $scaleH);
        $x = ($layoutFrente['x'] ?? 0) * $scale;
        $y = ($layoutFrente['y'] ?? 0) * $scale;
        $w = ($layoutFrente['w'] ?? 0) * $scale;
        $h = ($layoutFrente['h'] ?? 0) * $scale;
        $fs = ($layoutFrente['font_size'] ?? 20) * $scale;
        $ff = $layoutFrente['font_family'] ?? 'Arial';
        $fw = $layoutFrente['font_weight'] ?? 'normal';
        $fst = $layoutFrente['font_style'] ?? 'normal';
        $align = $layoutFrente['align'] ?? 'left';
        $styleFront = [
          "left:{$x}px",
          "top:{$y}px",
          "font-size:{$fs}px",
          "font-family:'{$ff}'",
          "font-weight:{$fw}",
          "font-style:{$fst}",
          "text-align:{$align}",
        ];
        if ($w > 0) $styleFront[] = "width:{$w}px";
        if ($h > 0) $styleFront[] = "height:{$h}px";
      @endphp
      <div class="text-layer" style="{{ implode(';', $styleFront) }}">{!! $renderStyled($textoFrente, $layoutFrente['styles'] ?? [], $fw, $fst) !!}</div>
    </div>
  </div>

  @if($versoUrl || $textoVerso)
  <div class="page">
    <div class="page-inner">
      @if($versoUrl)
        <img src="{{ $versoUrl }}" class="bg" alt="Verso">
      @endif
      @php
        $cw = max(1, (float)($layoutVerso['canvas_w'] ?? 2000));
        $ch = max(1, (float)($layoutVerso['canvas_h'] ?? 1100));
        $scaleW = 842.0 / $cw;
        $scaleH = 595.0 / $ch;
        $scale = min($scaleW, $scaleH);
        $x = ($layoutVerso['x'] ?? 0) * $scale;
        $y = ($layoutVerso['y'] ?? 0) * $scale;
        $w = ($layoutVerso['w'] ?? 0) * $scale;
        $h = ($layoutVerso['h'] ?? 0) * $scale;
        $fs = ($layoutVerso['font_size'] ?? 20) * $scale;
        $ff = $layoutVerso['font_family'] ?? 'Arial';
        $fw = $layoutVerso['font_weight'] ?? 'normal';
        $fst = $layoutVerso['font_style'] ?? 'normal';
        $align = $layoutVerso['align'] ?? 'left';
        $styleBack = [
          "left:{$x}px",
          "top:{$y}px",
          "font-size:{$fs}px",
          "font-family:'{$ff}'",
          "font-weight:{$fw}",
          "font-style:{$fst}",
          "text-align:{$align}",
        ];
        if ($w > 0) $styleBack[] = "width:{$w}px";
        if ($h > 0) $styleBack[] = "height:{$h}px";
      @endphp
      <div class="text-layer" style="{{ implode(';', $styleBack) }}">{!! $renderStyled($textoVerso, $layoutVerso['styles'] ?? [], $fw, $fst) !!}</div>
    </div>
  </div>
  @endif
</body>
</html>
