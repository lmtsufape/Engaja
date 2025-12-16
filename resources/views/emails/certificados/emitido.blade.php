@php
  $url = route('profile.certificados');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; background: #f7f7f7; padding: 0; margin: 0; }
    .container { max-width: 640px; margin: 20px auto; background: #fff; border: 1px solid #ececec; border-radius: 8px; padding: 20px; }
    .title { color: #421944; font-size: 20px; margin: 0 0 12px 0; }
    .btn { display: inline-block; padding: 10px 16px; background: #421944; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; }
    .muted { color: #6b7280; font-size: 14px; }
  </style>
</head>
<body>
  <div class="container">
    <p class="title">Olá, {{ $nome }}!</p>
    <p>Seu certificado referente à ação institucional <strong>{{ $acao }}</strong> está disponível no Engaja.</p>
    <p><a class="btn" href="{{ $url }}">Acessar meus certificados</a></p>
    <p class="muted">Caso o botão não funcione, copie e cole este link no navegador: {{ $url }}</p>
    <p class="muted">Esta é uma mensagem automática. Não responda este e-mail.</p>
  </div>
</body>
</html>
