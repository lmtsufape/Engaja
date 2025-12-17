@php
  $url = route('profile.certificados');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <style>
    body {
      font-family: 'Montserrat', Arial, sans-serif;
      background: #f5f3fa;
      padding: 0;
      margin: 0;
    }
    .wrapper {
      max-width: 720px;
      margin: 0 auto;
      padding: 32px 16px;
      text-align: center;
    }
    .logo {
      display: inline-block;
      margin-bottom: 16px;
    }
    .card {
      background: #fff;
      border-radius: 12px;
      padding: 28px 28px 24px 28px;
      box-shadow: 0 14px 35px rgba(0,0,0,0.06);
      text-align: left;
    }
    h1 {
      font-size: 22px;
      color: #1f2937;
      margin: 0 0 12px 0;
      font-weight: 700;
    }
    p {
      color: #374151;
      font-size: 15px;
      line-height: 1.6;
      margin: 0 0 14px 0;
    }
    .btn {
      display: inline-block;
      background: #4a0e4e;
      color: #fff;
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 700;
      margin: 6px 0 14px 0;
    }
    .muted {
      font-size: 13px;
      color: #6b7280;
      word-break: break-all;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="logo">
      @if(!empty($logoData))
        <img src="{{ $logoData }}" alt="Engaja" style="height:48px;">
      @endif
    </div>
    <div class="card">
      <h1>Olá, {{ $nome }}!</h1>
      <p>Seu certificado referente à ação pedagógica <strong>{{ $acao }}</strong> está disponível no Engaja.</p>
      <p>
        <a class="btn" href="{{ $url }}">Acessar meus certificados</a>
      </p>
      <p class="muted">Se o botão não funcionar, copie e cole este link no navegador:<br>{{ $url }}</p>
      <p class="muted">Esta é uma mensagem automática. Não responda este e-mail.</p>
    </div>
  </div>
</body>
</html>
