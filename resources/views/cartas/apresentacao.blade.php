@extends('cartas.layouts.app')

@section('title', 'Cartas para Esperançar')

@push('styles')
<style>
    :root {
        --land-paper: #F4F1ED;
        --land-ink: #00384B;          /* dark teal: headings / hero / eyebrow */
        --land-purple: #9602C7;
        --land-blue: #008BBC;
        --land-illustration: #008BBB;
        --land-step-body: #414652;
        --land-lilac: #E7C5F3;
        --land-yellow: #FDB913;
        --land-dashed: #BCBCBC;
        --land-topbar: #421944;
        --land-scale: 1;
    }

    .land {
        font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        color: var(--land-ink);
        background: var(--land-paper);
        font-size: calc(16px * var(--land-scale));
    }

    .land-container {
        width: min(100%, 972px);
        margin: 0 auto;
        padding: 0 24px;
    }

    /* ---------- Top bar (social) ---------- */
    .land-topbar {
        background: var(--land-topbar);
        color: #fff;
    }

    .land-topbar__inner {
        width: min(100%, 1440px);
        margin: 0 auto;
        padding: 20px 44px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 16px;
    }

    .land-topbar__label {
        font-size: 16px;
        font-weight: 500;
        color: #fff;
    }

    .land-social {
        display: inline-flex;
        align-items: center;
        gap: 12px;
    }

    .land-social a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        opacity: .95;
    }

    .land-social a img {
        display: block;
        height: 20px;
        width: auto;
    }

    .land-social a:hover {
        opacity: 1;
    }

    /* ---------- Nav ---------- */
    .land-nav {
        background: #fff;
        box-shadow: 0 4px 4px rgba(0, 0, 0, .06);
        position: relative;
        z-index: 2;
    }

    .land-nav__inner {
        width: min(100%, 1440px);
        margin: 0 auto;
        padding: 13px 44px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .land-nav__logo img {
        height: 34px;
        width: auto;
        display: block;
    }

    .land-nav__actions {
        display: flex;
        align-items: center;
        gap: 22px;
    }

    .land-login {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #000;
        text-decoration: none;
        font-size: 17px;
        font-weight: 500;
    }

    .land-login:hover {
        color: var(--land-purple);
    }

    .land-login__icon {
        display: block;
        height: 20px;
        width: auto;
    }

    .land-a11y__img {
        display: block;
        height: 19px;
        width: auto;
    }

    .land-important__icon {
        display: block;
        width: 24px;
        height: 24px;
        flex: none;
    }

    .land-a11y {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        color: #000;
    }

    .land-a11y__btn {
        border: 0;
        background: transparent;
        color: inherit;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 2px;
        font-weight: 600;
        font-family: 'Inter', 'Montserrat', sans-serif;
        line-height: 1;
    }

    .land-a11y__btn:hover {
        color: var(--land-purple);
    }

    .land-a11y__btn--plus {
        font-size: 18px;
    }

    .land-a11y__btn--minus {
        font-size: 16px;
    }

    .land-a11y__accessibility {
        color: #23a638;
    }

    /* ---------- Hero ---------- */
    .land-hero {
        background: var(--land-paper);
        text-align: center;
        padding: 72px 0 80px;
        min-height: 80vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .land-hero__group {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 32px;
    }

    .land-hero__heading {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }

    .land-hero__eyebrow {
        font-size: 14px;
        font-weight: 400;
        line-height: 1.2;
        color: var(--land-ink);
        margin: 0;
    }

    .land-hero__title {
        font-size: clamp(46px, 8.33vw, 120px);
        line-height: .9;
        font-weight: 700;
        color: var(--land-ink);
        margin: 0;
    }

    .land-hero__text {
        max-width: 738px;
        margin: 0 auto;
        font-size: 18px;
        font-weight: 400;
        line-height: 1.4;
        color: var(--land-ink);
    }

    /* ---------- Buttons ---------- */
    .land-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 44px;
        padding: 10px 18px;
        border: 1px solid var(--land-purple);
        border-radius: 8px;
        background: var(--land-purple);
        color: #fff;
        font-size: 16px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
        transition: background .15s ease;
    }

    .land-btn:hover {
        background: #7d02a6;
        border-color: #7d02a6;
        color: #fff;
    }

    .land-btn--ghost {
        background: transparent;
        border: 1px solid var(--land-paper);
        color: var(--land-paper);
    }

    .land-btn--ghost:hover {
        background: rgba(244, 241, 237, .14);
        border-color: var(--land-paper);
        color: var(--land-paper);
    }

    /* ---------- Purple section ---------- */
    .land-about {
        background: var(--land-purple);
        color: var(--land-paper);
        text-align: center;
        box-sizing: border-box;
        height: 664px;
        padding: 64px 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    /* largura de conteúdo 972px como no Figma (container 1020 - 48px de padding) */
    .land-about .land-container {
        width: min(100%, 1020px);
    }

    .land-about__group {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 40px;
    }

    .land-section-title {
        font-size: clamp(28px, 3vw, 40px);
        font-weight: 700;
        line-height: 1.08;
        margin: 0;
    }

    .land-about__text {
        margin: 0;
        font-size: 24px;
        font-weight: 400;
        line-height: 1.4;
        color: var(--land-paper);
    }

    /* Figma: paragraph spacing 8px + linha em branco (whitespace) a 10px/140% ≈ 14px */
    .land-about__text + .land-about__text {
        margin-top: 22px;
    }

    /* ---------- How to participate ---------- */
    .land-how {
        background: var(--land-paper);
        padding: 160px 0 140px;
    }

    /* largura de conteúdo 972px como no Figma */
    .land-how .land-container {
        width: min(100%, 1020px);
    }

    .land-how__title {
        font-size: clamp(28px, 3vw, 40px);
        font-weight: 700;
        line-height: 1.08;
        color: var(--land-ink);
        text-align: center;
        margin: 0;
    }

    .land-how__steps {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 160px;
        margin-top: 120px;
    }

    .land-step {
        max-width: 488px;
        position: relative;
        z-index: 1;
        padding-bottom: 40px; /* Figma: padding 0 0 40px nos frames dos passos */
    }

    .land-step--left {
        align-self: flex-start;
    }

    .land-step--right {
        align-self: flex-end;
    }

    .land-step__title {
        font-size: 24px;
        font-weight: 700;
        color: var(--land-purple);
        text-transform: uppercase;
        line-height: 1.08;
        margin: 0 0 8px;
    }

    .land-step__text {
        font-size: 20px;
        font-weight: 400;
        line-height: 1.4;
        color: var(--land-step-body);
        margin: 0 0 8px;
    }

    .land-step__text:last-child {
        margin-bottom: 0;
    }

    .land-illustration {
        position: absolute;
        z-index: 0;
        pointer-events: none;
    }

    .land-illustration img {
        display: block;
        height: auto;
    }

    .land-illustration--head img {
        width: 150px;
    }

    .land-illustration--book img {
        width: 140px;
    }

    .land-illustration--head {
        top: -24px;
        right: 24px;
    }

    .land-illustration--book {
        top: 42%;
        left: 50%;
        transform: translateX(-50%);
    }

    /* Conectores tracejados */
    .land-connector {
        position: absolute;
        z-index: 0;
        height: auto;
        pointer-events: none;
    }

    .land-connector--1 {
        top: -70px;
        left: 360px;
        width: 354px;
    }

    .land-connector--2 {
        top: 320px;
        left: 66px;
        width: 373px;
    }

    .land-connector--3 {
        top: 620px;
        left: 322px;
        width: 319px;
    }

    /* ---------- Important box ---------- */
    .land-important {
        background: var(--land-lilac);
        border-radius: 20px;
        padding: 20px;
        margin-top: 160px; /* + 40px de padding do passo 4 = 200px, como no Figma */
    }

    .land-important__head {
        display: flex;
        align-items: center;
        gap: 20px;
        font-size: 20px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--land-ink);
        margin-bottom: 20px;
    }

    .land-important__head svg {
        flex: none;
    }

    .land-important ul {
        margin: 0;
        padding-left: 24px;
    }

    .land-important li {
        font-size: 20px;
        font-weight: 500;
        line-height: 1.4;
        color: var(--land-ink);
        margin-bottom: 0; /* Figma: bloco único, separação só pelo line-height */
    }

    /* ---------- Blue closing ---------- */
    .land-closing {
        background: var(--land-blue);
        color: var(--land-paper);
        text-align: center;
        box-sizing: border-box;
        height: 664px;
        padding: 264px 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .land-closing__text {
        max-width: 810px;
        margin: 0 auto;
        font-size: clamp(18px, 2vw, 24px);
        font-weight: 400;
        line-height: 1.4;
    }

    @media (max-width: 760px) {
        .land-topbar__label {
            display: none;
        }

        .land-topbar__inner,
        .land-nav__inner {
            padding-left: 22px;
            padding-right: 22px;
        }

        .land-how__steps {
            gap: 56px;
        }

        .land-step,
        .land-step--left,
        .land-step--right {
            align-self: stretch;
            max-width: none;
        }

        .land-illustration,
        .land-illustration--head,
        .land-illustration--book {
            position: static;
            transform: none;
            display: flex;
            justify-content: center;
            margin: 8px 0;
        }

        .land-connector {
            display: none;
        }
    }

    /* Help Tutorial Tooltip */
    .help-tutorial-tooltip {
        position: fixed;
        bottom: 96px;
        right: 28px;
        background-color: var(--land-purple);
        color: #fff;
        padding: 12px 18px;
        border-radius: 8px;
        font-family: 'Montserrat', sans-serif;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 4px 14px rgba(169, 0, 217, 0.32);
        z-index: 2400;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: opacity 0.4s ease, transform 0.4s ease, visibility 0.4s ease;
        pointer-events: none;
        text-align: center;
    }
    .help-tutorial-tooltip.is-visible {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    .help-tutorial-tooltip__arrow {
        position: absolute;
        bottom: -6px;
        right: 22px;
        width: 12px;
        height: 12px;
        background-color: var(--land-purple);
        transform: rotate(45deg);
    }
</style>
@endpush

@section('body')
<div class="land">

    {{-- ============ Top bar ============ --}}
    <div class="land-topbar">
        <div class="land-topbar__inner">
            <span class="land-topbar__label">Nos acompanhe nas redes:</span>
            <span class="land-social">
                <a href="https://www.youtube.com/@alfaejabrasil" target="_blank" rel="noopener" aria-label="YouTube" ><img src="{{ asset('images/cartas/youtube.svg') }}" alt="YouTube"></a>
                <a href="https://www.instagram.com/alfaejabrasil" target="_blank" rel="noopener" aria-label="Instagram"><img src="{{ asset('images/cartas/instagram.svg') }}" alt="Instagram"></a>
                <a href="https://www.facebook.com/profile.php?id=61574396997436" target="_blank" rel="noopener" aria-label="Facebook"><img src="{{ asset('images/cartas/facebook.svg') }}" alt="Facebook"></a>
            </span>
        </div>
    </div>

    {{-- ============ Nav ============ --}}
    <nav class="land-nav">
        <div class="land-nav__inner">
            <a href="{{ route('cartas.apresentacao') }}" class="land-nav__logo" aria-label="Cartas para Esperançar">
                <img src="{{ asset('images/cartas/cartas-logo.png') }}" alt="Cartas para Esperançar">
            </a>

            <div class="land-nav__actions">
                <a href="{{ route('cartas.login') }}" class="land-login">
                    <img src="{{ asset('images/cartas/log-out.png') }}" alt="" class="land-login__icon">
                    Login
                </a>

                <div class="land-a11y">
                    <button type="button" class="land-a11y__btn land-a11y__btn--plus" data-a11y="increase" aria-label="Aumentar tamanho do texto">+A</button>
                    <button type="button" class="land-a11y__btn land-a11y__btn--minus" data-a11y="decrease" aria-label="Diminuir tamanho do texto">-A</button>
                    <button type="button" class="land-a11y__btn" data-a11y="theme" aria-label="Alternar contraste">
                        <img src="{{ asset('images/cartas/icone-lua.png') }}" alt="" class="land-a11y__img">
                    </button>
                    <button type="button" class="land-a11y__btn" aria-label="Idioma / Acessibilidade">
                        <img src="{{ asset('images/cartas/bandeira-brasil.png') }}" alt="Brasil" class="land-a11y__img">
                    </button>
                </div>
            </div>
        </div>
    </nav>

    {{-- ============ Hero ============ --}}
    <header class="land-hero">
        <div class="land-container">
            <div class="land-hero__group">
                <div class="land-hero__heading">
                    <p class="land-hero__eyebrow">Instituto Paulo Freire apresenta:</p>
                    <h1 class="land-hero__title">Cartas para esperançar</h1>
                </div>
                <p class="land-hero__text">
                    Bem-vindo(a)!<br>
                    Você está prestes a participar da ação pedagógica 'Cartas para Esperançar', uma
                    iniciativa do Projeto ALFA-EJA Brasil, realizado pelo Instituto Paulo Freire em
                    parceria com a Petrobras.
                </p>
                <a href="{{ route('cartas.register') }}" class="land-btn">Participar</a>
            </div>
        </div>
    </header>

    {{-- ============ O que é ============ --}}
    <section class="land-about">
        <div class="land-container">
            <div class="land-about__group">
                <h2 class="land-section-title">O que é o Cartas para Esperançar?</h2>
                <div class="land-about__texts">
                    <p class="land-about__text">
                        'Cartas para Esperançar' propõe um diálogo humanizador e afetivo entre os(as)
                        educandos(as) da EJA dos 15 municípios participantes do Projeto ALFA-EJA
                        Brasil e os(as) funcionários(as) voluntários(as) da Petrobras.
                    </p>
                    <p class="land-about__text">
                        A carta, em seu formato manuscrito ou organizado por meio de outras formas de
                        expressão, torna-se um território de encontro, onde sonhos, histórias, lutas e
                        esperanças de sujeitos de realidades distintas podem se reconhecer e se
                        fortalecer mutuamente.
                    </p>
                </div>
                <a href="{{ route('cartas.register') }}" class="land-btn land-btn--ghost">Participar</a>
            </div>
        </div>
    </section>

    {{-- ============ Como participar ============ --}}
    <section class="land-how">
        <div class="land-container">
            <h2 class="land-how__title">Como participar?</h2>

            <div class="land-how__steps">
                {{-- Conectores tracejados --}}
                <img src="{{ asset('images/cartas/seta-tracejada-1.png') }}" alt="" class="land-connector land-connector--1" aria-hidden="true">
                <img src="{{ asset('images/cartas/seta-tracejada-2.png') }}" alt="" class="land-connector land-connector--2" aria-hidden="true">
                <img src="{{ asset('images/cartas/seta-tracejada-3.png') }}" alt="" class="land-connector land-connector--3" aria-hidden="true">

                {{-- Step 1 --}}
                <div class="land-step land-step--left">
                    <h3 class="land-step__title">1. Crie sua conta e acesse</h3>
                    <p class="land-step__text">Cadastre-se usando seu login e sua senha e então acesse.</p>
                </div>

                {{-- Head illustration --}}
                <div class="land-illustration land-illustration--head" aria-hidden="true">
                    <img src="{{ asset('images/cartas/ilustracao-lendo.png') }}" alt="">
                </div>

                {{-- Step 2 --}}
                <div class="land-step land-step--right">
                    <h3 class="land-step__title">2. Verifique se você tem correspondência</h3>
                    <p class="land-step__text">Leia com, carinho, a carta que foi enviada para você.</p>
                </div>

                {{-- Book illustration --}}
                <div class="land-illustration land-illustration--book" aria-hidden="true">
                    <img src="{{ asset('images/cartas/livro-azul.png') }}" alt="">
                </div>

                {{-- Step 3 --}}
                <div class="land-step land-step--left">
                    <h3 class="land-step__title">3. Responda à carta</h3>
                    <p class="land-step__text">Você pode digitar ou escrever sua carta manualmente.</p>
                    <p class="land-step__text">Sua carta deve ser respeitosa e acolhedora.</p>
                    <p class="land-step__text">Você pode compartilhar experiências, reflexões, aprendizados, sonhos, histórias de vida ou mensagens de incentivo.</p>
                    <p class="land-step__text">Assine sua carta com seu nome.</p>
                </div>

                {{-- Step 4 --}}
                <div class="land-step land-step--right">
                    <h3 class="land-step__title">4. Confirme o envio</h3>
                    <p class="land-step__text">Após enviar a sua carta, você receberá uma mensagem confirmando que sua participação foi concluída.</p>
                </div>
            </div>

            {{-- Important box --}}
            <div class="land-important">
                <div class="land-important__head">
                    <img src="{{ asset('images/cartas/light-bulb-on.png') }}" alt="" class="land-important__icon">
                    Importante
                </div>
                <ul>
                    <li>Utilize linguagem respeitosa e cordial.</li>
                    <li>Não compartilhe dados pessoais sensíveis.</li>
                    <li>Não publique conteúdos discriminatórios, ofensivos ou que violem direitos de terceiros.</li>
                    <li>O conteúdo poderá passar por acompanhamento pedagógico e organizacional antes de sua destinação.</li>
                    <li>Você pode digitalizar a sua carta ou anexá-la manuscrita.</li>
                    <li>O objetivo da ação é promover diálogo, reconhecimento e construção coletiva de esperança.</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- ============ Closing (blue) ============ --}}
    <section class="land-closing">
        <div class="land-container">
            <p class="land-closing__text">
                Cada carta enviada é mais do que uma correspondência. É um encontro entre
                pessoas, histórias e saberes. Obrigado(a) por contribuir para esta rede de
                diálogo, respeito e esperança construída pelo Projeto ALFA-EJA Brasil.
            </p>
        </div>
    </section>

</div>
    <div id="helpTutorialTooltip" class="help-tutorial-tooltip">
        Precisa de ajuda? Clique aqui!
        <div class="help-tutorial-tooltip__arrow"></div>
    </div>
    @include('cartas.shared._help_fab')
@endsection

@push('scripts')
<script>
    (function () {
        const root = document.documentElement;
        let scale = 1;

        document.querySelectorAll('[data-a11y]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const action = btn.dataset.a11y;

                if (action === 'increase') {
                    scale = Math.min(scale + 0.1, 1.4);
                } else if (action === 'decrease') {
                    scale = Math.max(scale - 0.1, 0.85);
                } else if (action === 'theme') {
                    document.querySelector('.land').classList.toggle('land--contrast');
                    return;
                }

                root.style.setProperty('--land-scale', scale.toFixed(2));
            });
        });

        // Help Tutorial Logic
        const tooltip = document.getElementById('helpTutorialTooltip');
        const fabToggle = document.getElementById('helpFabToggle');
        
        if (tooltip && fabToggle) {
            // Show after a small delay
            setTimeout(() => {
                tooltip.classList.add('is-visible');
            }, 800);

            // Hide after 7 seconds
            const hideTimeout = setTimeout(() => {
                tooltip.classList.remove('is-visible');
            }, 7000);

            // Hide when clicked
            fabToggle.addEventListener('click', () => {
                tooltip.classList.remove('is-visible');
                clearTimeout(hideTimeout);
            });
        }
    })();
</script>
@endpush
