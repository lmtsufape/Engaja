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
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            max-width: 720px;
            margin: 0 auto;
            padding: 32px 16px;
            text-align: center;
        }
        .logo {
            display: inline-block;
            margin-bottom: 24px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: left;
        }
        h1 {
            font-size: 22px;
            color: #1f2937;
            margin: 0 0 16px 0;
            font-weight: 700;
        }
        p {
            color: #374151;
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 16px 0;
        }
        .btn {
            display: inline-block;
            background: #4a0e4e;
            color: #fff;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            margin: 8px 0 16px 0;
            text-align: center;
        }
        .info-box {
            background-color: #f8fafc;
            border-left: 4px solid #4a0e4e;
            padding: 16px 20px;
            border-radius: 0 8px 8px 0;
            margin: 32px 0 24px 0;
        }
        .info-box-title {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #4b5563;
        }
        .password-highlight {
            background-color: #e5e7eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
            color: #111827;
        }
        hr {
            border: 0;
            border-top: 1px solid #e5e7eb;
            margin: 32px 0 24px 0;
        }
        .muted {
            font-size: 13px;
            color: #6b7280;
            word-break: break-all;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="logo">
        @if(isset($logoPath) && file_exists($logoPath))
            <img src="{{ $message->embed($logoPath) }}" alt="Plataforma Engaja" style="height:48px;">
        @endif
    </div>

    <div class="card">
        <h1>
            Olá, {{ $nome }}! 🤗
        </h1>

        <p>
            🎉 É com imensa alegria que informamos que o seu certificado de participação no projeto <strong>ALFA-EJA Brasil</strong>, já está disponível!
        </p>

        <p>
            Este certificado reconhece seu percurso de formação, marcado por estudo, reflexão, diálogo e troca de saberes, e sua participação na construção de uma educação comprometida com a transformação social.
        </p>

        <p>
            Para acessar e baixar o seu certificado na plataforma Engaja, clique no botão abaixo:
        </p>

        <div style="text-align: center;">
            <a class="btn" href="{{ $url }}" style="color: #ffffff !important; text-decoration: none;">Acessar meus certificados</a>
        </div>

        <div class="info-box">
            <div class="info-box-title">📌 Instruções importantes de acesso</div>
            <p>
                Caso ainda não tenha se conectado a plataforma, utilize este seu e-mail cadastrado e a senha temporária <span class="password-highlight">alfaeja2025</span> para entrar.
            </p>

            <p style="margin-top: 12px;">
                Se a senha temporária não funcionar, clique na opção <strong>"Esqueceu a senha?"</strong>, na tela de login, e informe este seu endereço de e-mail para cadastrar uma nova senha.
            </p>
        </div>

        <p>
            💬 Precisa de ajuda? Estamos à disposição!
        </p>

        <p>
            Se encontrar qualquer dificuldade para acessar a plataforma ou baixar o seu certificado, entre em contato com nossa equipe pelo canal de suporte:
        </p>

        <p>
            👉 <a href="https://chat.whatsapp.com/Do6SVDfTQWL3kwqVLephTY">Acessar canal de suporte no WhatsApp</a>
        </p>

        <p>
            🤝 Agradecemos por fazer parte dessa jornada formativa. Seguimos em diálogo e na construção coletiva de uma educação transformadora!

        </p>

        <p style="margin-top: 24px;">
            Com carinho,<br>
            <strong>Equipe do Projeto ALFA-EJA Brasil</strong>
        </p>

        <hr>

        <p class="muted">Se o botão acima não funcionar, copie e cole este link no seu navegador:<br><a href="{{ $url }}" style="color: #4a0e4e;">{{ $url }}</a></p>
        <p class="muted">Esta é uma mensagem automática do sistema. Por favor, não responda a este e-mail.</p>
    </div>
</div>
</body>
</html>
