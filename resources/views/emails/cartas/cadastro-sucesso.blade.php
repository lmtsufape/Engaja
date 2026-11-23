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
    h2 {
      font-size: 17px;
      color: #1f2937;
      margin: 24px 0 12px;
      font-weight: 700;
      line-height: 1.3;
    }
    p {
      color: #374151;
      font-size: 15px;
      line-height: 1.65;
      margin: 0 0 16px;
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
      <div class="card-header">
        <span class="label">Cadastro confirmado</span>
        <h1>Seu cadastro no Cartas para Esperançar está confirmado</h1>
      </div>

      <p>Olá!</p>

      <p>
        Seu cadastro na plataforma <strong>Cartas para Esperançar</strong> foi realizado com sucesso. A partir de agora, você faz parte de uma rede de troca muito especial.
      </p>

      <h2>Como funciona:</h2>

      <p>
        Em breve, uma carta escrita por um educando ou educanda da EJA vai chegar até você. São histórias de vida, conquistas na sala de aula, sonhos, trabalho, festas, medos: o cotidiano de quem decidiu voltar a estudar.
      </p>

      <p>
        Seu papel é responder. Na plataforma ou à mão, com letra cursiva ou de forma, criando uma resposta pessoal e assinada. Ao contar um pouco da sua própria trajetória e dialogar com o que leu, você cria um vínculo real.
      </p>

      <p>
        Em breve, um novo e-mail será enviado quando sua primeira carta estiver disponível.
      </p>

      <p style="margin-top: 24px;">
        Obrigado por esperançar com a gente.<br>
        <strong>Projeto ALFA-EJA Brasil | Cartas para Esperançar</strong>
      </p>
    </div>

    <div class="footer">
      Projeto ALFA-EJA Brasil | Cartas para Esperançar
    </div>
  </div>
</body>
</html>
