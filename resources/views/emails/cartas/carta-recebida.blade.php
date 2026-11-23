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
      max-width: 620px;
      margin: 0 auto;
      padding: 32px 16px;
    }
    .logo-area {
      text-align: center;
      margin-bottom: 20px;
    }
    .card {
      background: #fff;
      border-radius: 12px;
      padding: 32px 32px 28px;
      box-shadow: 0 14px 35px rgba(0,0,0,0.06);
    }
    .card-header {
      border-bottom: 1px solid #f0ece8;
      padding-bottom: 18px;
      margin-bottom: 22px;
    }
    .label {
      display: inline-block;
      background: #fff3e0;
      color: #b45309;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      padding: 4px 10px;
      border-radius: 999px;
      margin-bottom: 12px;
    }
    h1 {
      font-size: 21px;
      color: #1f2937;
      margin: 0;
      font-weight: 700;
      line-height: 1.3;
    }
    p {
      color: #374151;
      font-size: 15px;
      line-height: 1.65;
      margin: 0 0 16px;
    }
    .btn {
      display: inline-block;
      background: #7c3aed;
      color: #fff !important;
      padding: 13px 28px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 700;
      font-size: 15px;
      margin: 8px 0 20px;
    }
    ul {
      color: #374151;
      font-size: 15px;
      line-height: 1.65;
      margin: 0 0 16px;
      padding-left: 20px;
    }
    li {
      margin-bottom: 8px;
    }
    .muted {
      font-size: 13px;
      color: #6b7280;
      word-break: break-all;
      margin-top: 0;
    }
    .footer {
      text-align: center;
      margin-top: 24px;
      font-size: 12px;
      color: #9ca3af;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="logo-area">
      <img src="{{ asset('images/cartas/cartas-logo.png') }}" alt="Cartas para Esperançar" style="max-height:60px;width:auto;">
    </div>

    <div class="card">
      @if($isPrimeiraVez)
        <div class="card-header">
          <span class="label">Chegou uma carta</span>
          <h1>Chegou uma carta para você!</h1>
        </div>

        <p>Olá!</p>

        <p>
          Uma carta acabou de chegar na plataforma <strong>Cartas para Esperançar</strong>. Ela foi escrita por um educando ou educanda da EJA e agora está esperando sua resposta!
        </p>

        <p>
          <a class="btn" href="{{ $url }}">ACESSAR MINHA CARTA</a>
        </p>

        <p><strong>Antes de responder, algumas orientações:</strong></p>
        <ul>
          <li>Escreva direto na Plataforma. Tem um campo para isso ou escreva à mão, com letra cursiva ou de forma;</li>
          <li>Assine seu nome. Quem escreveu para você não sabe quem vai ler; ao se identificar, você fecha essa ponte;</li>
          <li>Conte um pouco de você: seu trabalho, sua história, o que a leitura despertou;</li>
          <li>Dialogue com o que leu. A carta que você recebeu tem perguntas, histórias e afetos. Responda a eles;</li>
          <li>Se escrever à mão, fotografe ou digitalize sua resposta e anexe em formato PDF na plataforma.</li>
        </ul>

        <p>
          Sua resposta será impressa, colocada em um envelope e entregue pessoalmente ao educando ou educanda, em um momento de celebração na sala de aula.
        </p>

        <p>
          Boa leitura e boa escrita.<br>
          <strong>Projeto ALFA-EJA Brasil | Cartas para Esperançar</strong>
        </p>
      @else
        <div class="card-header">
          <span class="label">Resposta recebida</span>
          <h1>Sua carta foi respondida</h1>
        </div>

        <p>Olá!</p>

        <p>
          A conversa continua: chegou uma resposta à carta que você enviou pelo <strong>Cartas para Esperançar</strong>.
        </p>

        <p>
          <a class="btn" href="{{ $url }}">LER A RESPOSTA</a>
        </p>

        <p>
          Do outro lado dessa correspondência há alguém que leu suas palavras com atenção e decidiu escrever de volta. Cada troca aprofunda o vínculo e transforma duas realidades distintas em um território comum.
        </p>

        <p>
          Se quiser seguir a conversa, é só escrever uma nova carta à mão, digitalizar em PDF e anexar na plataforma.
        </p>

        <p style="margin-top: 24px;">
          Atenciosamente,<br>
          <strong>Projeto ALFA-EJA Brasil | Cartas para Esperançar</strong>
        </p>
      @endif

      <p class="muted">Se o botão não funcionar, copie e cole este link no navegador:<br>{{ $url }}</p>
    </div>

    <div class="footer">
      Projeto ALFA-EJA Brasil | Cartas para Esperançar
    </div>
  </div>
</body>
</html>
