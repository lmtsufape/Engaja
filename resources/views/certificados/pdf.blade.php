<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <style>

    @import url('https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,400;0,600;0,700;1,400&family=Open+Sans:ital,wght@0,400;0,700;1,400&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Roboto:ital,wght@0,400;0,700;1,400&display=swap');

    @page { size: A4 landscape; margin: 0; }
    html, body { margin: 0; padding: 0; width: 297mm; height: 210mm; font-family: Arial, sans-serif; }
    .page {
      position: relative;
      width: 297mm;
      height: 210mm;
      page-break-after: always;
      overflow: hidden;
    }
    .page:last-of-type {
      page-break-after: auto;
    }
    .bg {
      /* Preenche a página inteira preservando proporção (cover). A folha do PDF é
         alinhada ao @page via preferCSSPageSize (ver CertificadoController), o que
         elimina a faixa branca de borda causada por arredondamento mm→folha. */
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .text-layer {
      position: absolute;
      color: #111;
      font-size: 20px;
      line-height: 1.4;
      white-space: normal;
      margin: 0;
      padding: 0;
    }
  </style>
</head>
<body>
  @php
    $mmToPx = 96 / 25.4;
    $pageW = 297 * $mmToPx; // largura A4 paisagem em px (96dpi)
    $pageH = 210 * $mmToPx; // altura A4 paisagem em px (96dpi)
    $modelo = $certificado->modelo;
    $frenteFile = $modelo?->imagem_frente ? public_path('storage/'.$modelo->imagem_frente) : null;
    $versoFile  = $modelo?->imagem_verso ? public_path('storage/'.$modelo->imagem_verso) : null;
    $layoutFrente = $modelo->layout_frente ?? [];
    $layoutVerso  = $modelo->layout_verso ?? [];
    $textoFrente = trim($certificado->texto_frente ?? '');
    $textoVerso  = trim($certificado->texto_verso ?? '');
    $dataEmissaoPadrao = ($certificado->created_at ?? now())->locale('pt_BR')->translatedFormat('j \d\e F \d\e Y');
    $textoDataEmissao = $layoutFrente['date_text'] ?? "São Paulo, {$dataEmissaoPadrao}.";
    $qrLink = $certificado->codigo_validacao ? route('certificados.validacao', $certificado->codigo_validacao) : null;
    $qrBase64 = null;
    $qrColorHex = $layoutVerso['qr_color'] ?? '#811283';
    $qrColorHex = ltrim($qrColorHex, '#');
    $qrColorHex = preg_match('/^[0-9a-fA-F]{6}$/', $qrColorHex) ? $qrColorHex : '811283';
    $qrRGB = [
        hexdec(substr($qrColorHex, 0, 2)),
        hexdec(substr($qrColorHex, 2, 2)),
        hexdec(substr($qrColorHex, 4, 2)),
    ];
    if ($qrLink && class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
        $qrFormat = env('QR_CODE_FORMAT', 'png');
        $qrData = \SimpleSoftwareIO\QrCode\Facades\QrCode::format($qrFormat)
            ->style('round')
            ->eye('circle')
            ->eyeColor(0, $qrRGB[0], $qrRGB[1], $qrRGB[2], $qrRGB[0], $qrRGB[1], $qrRGB[2])
            ->eyeColor(1, $qrRGB[0], $qrRGB[1], $qrRGB[2], $qrRGB[0], $qrRGB[1], $qrRGB[2])
            ->eyeColor(2, $qrRGB[0], $qrRGB[1], $qrRGB[2], $qrRGB[0], $qrRGB[1], $qrRGB[2])
            ->color($qrRGB[0], $qrRGB[1], $qrRGB[2])
            ->margin(0)
            ->size(280)
            ->errorCorrection('H')
            ->generate($qrLink);
        $qrMime = $qrFormat === 'svg' ? 'image/svg+xml' : 'image/'.$qrFormat;
        $qrBase64 = "data:{$qrMime};base64,".base64_encode($qrData);
    }
    $frenteInfo = ($frenteFile && file_exists($frenteFile)) ? @getimagesize($frenteFile) : null;
    $versoInfo  = ($versoFile && file_exists($versoFile)) ? @getimagesize($versoFile) : null;

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
        // white background for PNG/GIF transparency
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

    $renderStyled = function ($text, $layout) {
        $styles = $layout['styles'] ?? [];
        $lines = explode("\n", (string) $text);
        $parts = [];
        foreach ($lines as $rowIndex => $line) {
            $lineStyles = $styles[$rowIndex] ?? [];
            $chars = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY);
            $buffer = '';
            $prevStyle = null;
            $boldFlag = false;
            $flush = function () use (&$buffer, &$prevStyle, &$parts) {
                if ($buffer === '') {
                    return;
                }
                $open = '';
                $close = '';
                if (($prevStyle['fontWeight'] ?? '') === 'bold') {
                    $open .= '<strong>';
                    $close = '</strong>' . $close;
                }
                if (($prevStyle['fontStyle'] ?? '') === 'italic') {
                    $open .= '<em>';
                    $close = '</em>' . $close;
                }
                $parts[] = $open . e($buffer) . $close;
                $buffer = '';
            };

            foreach ($chars as $idx => $ch) {
                if ($ch === '*') {
                    // alterna negrito com marcador *...*
                    $flush();
                    $boldFlag = ! $boldFlag;
                    $prevStyle = null;
                    continue;
                }
                $current = $lineStyles[$idx] ?? [];
                if ($boldFlag) {
                    $current['fontWeight'] = 'bold';
                }
                if ($prevStyle === null) {
                    $prevStyle = $current;
                    $buffer .= $ch;
                    continue;
                }
                if ($current === $prevStyle) {
                    $buffer .= $ch;
                } else {
                    $flush();
                    $prevStyle = $current;
                    $buffer .= $ch;
                }
            }
            $flush();
            // quebra de linha
            if ($rowIndex < count($lines) - 1) {
                $parts[] = '<br>';
            }
        }
        return implode('', $parts);
    };
  @endphp

  <div class="page">
    @php
      $imgW = max(1, (float)($frenteInfo[0] ?? 2000));
      $imgH = max(1, (float)($frenteInfo[1] ?? 1100));
      $cw = max(1, (float)($layoutFrente['canvas_w'] ?? $imgW));
      $ch = max(1, (float)($layoutFrente['canvas_h'] ?? $imgH));
      $scale = max($pageW / $imgW, $pageH / $imgH);
      $renderW = $imgW * $scale;
      $renderH = $imgH * $scale;
      $offsetX = ($pageW - $renderW) / 2;
      $offsetY = ($pageH - $renderH) / 2;
      $scaleX = $renderW / $cw;
      $scaleY = $renderH / $ch;
      $x = $offsetX + ($layoutFrente['x'] ?? 0) * $scaleX;
      $y = $offsetY + ($layoutFrente['y'] ?? 0) * $scaleY;
      $w = ($layoutFrente['w'] ?? 0) * $scaleX;
      $h = ($layoutFrente['h'] ?? 0) * $scaleY;
      $fs = ($layoutFrente['font_size'] ?? 20) * min($scaleX, $scaleY);
      $ff = $layoutFrente['font_family'] ?? 'Arial';
      $fw = $layoutFrente['font_weight'] ?? 'normal';
      $fst = $layoutFrente['font_style'] ?? 'normal';
      $align = $layoutFrente['align'] ?? 'left';
      $colorFrente = $layoutFrente['text_color'] ?? '#111111';
      $boxW = $w > 0 ? $w : $renderW;
      $boxH = $h > 0 ? $h : null;
      $styleFront = [
        "left:{$x}px",
        "top:{$y}px",
        "font-size:{$fs}px",
        "font-family:'{$ff}'",
        "font-weight:{$fw}",
        "font-style:{$fst}",
        "text-align:{$align}",
        "color:{$colorFrente}",
      ];
      if ($align === 'justify') {
          $styleFront[] = "text-align-last:left";
          $styleFront[] = "text-justify:inter-word";
      } else {
          $styleFront[] = "text-align-last:{$align}";
      }

      if ($boxW) $styleFront[] = "width:{$boxW}px";
      if ($boxH) $styleFront[] = "height:{$boxH}px";
      $dateX = $offsetX + ($layoutFrente['date_x'] ?? 0) * $scaleX;
      $dateY = $offsetY + ($layoutFrente['date_y'] ?? 0) * $scaleY;
      $dateW = ($layoutFrente['date_w'] ?? 320) * $scaleX;
      $dateH = ($layoutFrente['date_h'] ?? 0) * $scaleY;
      $dateFs = ($layoutFrente['font_size'] ?? 20) * min($scaleX, $scaleY);
      $dateFf = $layoutFrente['font_family'] ?? 'Arial';
      $dateFw = $layoutFrente['font_weight'] ?? 'normal';
      $dateFst = $layoutFrente['font_style'] ?? 'normal';
      $dateAlign = $layoutFrente['date_align'] ?? 'left';
      $styleDateFront = [
        "left:{$dateX}px",
        "top:{$dateY}px",
        "font-size:{$dateFs}px",
        "font-family:'{$dateFf}'",
        "font-weight:{$dateFw}",
        "font-style:{$dateFst}",
        "text-align:{$dateAlign}",
        "width:{$dateW}px",
        "color:{$colorFrente}",
      ];
      if ($dateAlign === 'justify') {
          $styleDateFront[] = "text-align-last:left";
          $styleDateFront[] = "text-justify:inter-word";
      } else {
          $styleDateFront[] = "text-align-last:{$dateAlign}";
      }
      if ($dateH > 0) $styleDateFront[] = "height:{$dateH}px";
    @endphp
    <img src="{{ $frenteUrl }}" class="bg" alt="Frente">
    <div class="text-layer" style="{{ implode(';', $styleFront) }}">{!! $renderStyled($textoFrente, $layoutFrente) !!}</div>
    @if(is_numeric($layoutFrente['date_x'] ?? null) && is_numeric($layoutFrente['date_y'] ?? null))
      <div class="text-layer" style="{{ implode(';', $styleDateFront) }}">{{ $textoDataEmissao }}</div>
    @endif
  </div>

  @if($versoUrl || $textoVerso)
  <div class="page">
    @php
      $imgW = max(1, (float)($versoInfo[0] ?? 2000));
      $imgH = max(1, (float)($versoInfo[1] ?? 1100));
      $cw = max(1, (float)($layoutVerso['canvas_w'] ?? $imgW));
      $ch = max(1, (float)($layoutVerso['canvas_h'] ?? $imgH));
      $scale = max($pageW / $imgW, $pageH / $imgH);
      $renderW = $imgW * $scale;
      $renderH = $imgH * $scale;
      $offsetX = ($pageW - $renderW) / 2;
      $offsetY = ($pageH - $renderH) / 2;
      $scaleX = $renderW / $cw;
      $scaleY = $renderH / $ch;
      $x = $offsetX + ($layoutVerso['x'] ?? 0) * $scaleX;
      $y = $offsetY + ($layoutVerso['y'] ?? 0) * $scaleY;
      $w = ($layoutVerso['w'] ?? 0) * $scaleX;
      $h = ($layoutVerso['h'] ?? 0) * $scaleY;
      $fs = ($layoutVerso['font_size'] ?? 20) * min($scaleX, $scaleY);
      $ff = $layoutVerso['font_family'] ?? 'Arial';
      $fw = $layoutVerso['font_weight'] ?? 'normal';
      $fst = $layoutVerso['font_style'] ?? 'normal';
      $align = $layoutVerso['align'] ?? 'left';
      $colorVerso = $layoutVerso['text_color'] ?? '#111111';
      $boxW = $w > 0 ? $w : $renderW;
      $boxH = $h > 0 ? $h : null;
      $styleBack = [
        "left:{$x}px",
        "top:{$y}px",
        "font-size:{$fs}px",
        "font-family:'{$ff}'",
        "font-weight:{$fw}",
        "font-style:{$fst}",
        "text-align:{$align}",
        "color:{$colorVerso}",
      ];
      if ($align === 'justify') {
          $styleBack[] = "text-align-last:left";
          $styleBack[] = "text-justify:inter-word";
      } else {
          $styleBack[] = "text-align-last:{$align}";
      }

      if ($boxW) $styleBack[] = "width:{$boxW}px";
      if ($boxH) $styleBack[] = "height:{$boxH}px";
      $qrX = $offsetX + (($layoutVerso['qr_x'] ?? null) !== null ? $layoutVerso['qr_x'] * $scaleX : 0);
      $qrY = $offsetY + (($layoutVerso['qr_y'] ?? null) !== null ? $layoutVerso['qr_y'] * $scaleY : 0);
      $qrS = ($layoutVerso['qr_size'] ?? 0) * min($scaleX, $scaleY);
    @endphp
    @if($versoUrl)
      <img src="{{ $versoUrl }}" class="bg" alt="Verso">
    @endif
    @if($qrBase64 && ($layoutVerso['qr_size'] ?? null))
      <img src="{{ $qrBase64 }}" alt="QR" style="position:absolute; left:{{ $qrX }}px; top:{{ $qrY }}px; width:{{ $qrS }}px; height:{{ $qrS }}px; object-fit:contain;">
    @endif
    <div class="text-layer" style="{{ implode(';', $styleBack) }}">{!! $renderStyled($textoVerso, $layoutVerso) !!}</div>
  </div>
  @endif
</body>
</html>
