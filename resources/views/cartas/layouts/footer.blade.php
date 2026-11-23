<footer class="cpe-ft">

    {{-- ============ Faixa rosa: redes sociais ============ --}}
    <div class="cpe-ft__social-band">
        <div class="cpe-ft__inner">
            <h2 class="cpe-ft__social-title">Acesse mais conteúdos em:</h2>
            <div class="cpe-ft__social">
                <a href="https://www.youtube.com/@alfaejabrasil" target="_blank" rel="noopener" class="cpe-ft__social-item" aria-label="YouTube">
                    <img src="{{ asset('images/cartas/youtube.svg') }}" alt="">
                    <span>alfaejabrasil</span>
                </a>
                <a href="https://www.instagram.com/alfaejabrasil" target="_blank" rel="noopener" class="cpe-ft__social-item" aria-label="Instagram">
                    <img src="{{ asset('images/cartas/instagram.svg') }}" alt="">
                    <span>@alfaejabrasil</span>
                </a>
                <a href="https://www.facebook.com/profile.php?id=61574396997436" target="_blank" rel="noopener" class="cpe-ft__social-item" aria-label="Facebook">
                    <img src="{{ asset('images/cartas/facebook.svg') }}" alt="">
                    <span>@alfaejabrasil</span>
                </a>
            </div>
        </div>
    </div>

    {{-- ============ Faixa colorida ondulada ============ --}}
    <div class="cpe-ft__wave" aria-hidden="true">
        <img src="{{ asset('images/cartas/faixas-coloridas.svg') }}" alt="">
    </div>

    {{-- ============ Rodapé roxo ============ --}}
    <div class="cpe-ft__main">
        <div class="cpe-ft__inner">

            <div class="cpe-ft__top">
                {{-- Marca --}}
                <div class="cpe-ft__brand">
                    <div class="cpe-ft__brand-lockup">
                        <img src="{{ asset('images/cartas/alfaeja-icone.svg') }}" alt="" class="cpe-ft__brand-icon">
                        <span class="cpe-ft__brand-name">ALFA-EJA<br>Brasil</span>
                    </div>
                    <p class="cpe-ft__brand-tag">Educação para jovens, adultos e idosos.</p>
                    <img src="{{ asset('images/cartas/cartas-icone-branco.png') }}" alt="Cartas para Esperançar" class="cpe-ft__brand-cartas">
                </div>

                {{-- Navegação --}}
                <nav class="cpe-ft__nav">
                    <a href="https://alfaejabrasil.org.br/home" target="_blank" rel="noopener">Início</a>
                    <a href="https://alfaejabrasil.org.br/blogs" target="_blank" rel="noopener">Blog</a>
                    <a href="https://alfaejabrasil.org.br/atualizacoes" target="_blank" rel="noopener">Notícias</a>
                    <a href="https://alfaejabrasil.org.br/podcast" target="_blank" rel="noopener">Podcast</a>
                    <a href="https://alfaejabrasil.org.br/imprensa" target="_blank" rel="noopener">Imprensa</a>
                </nav>

                <nav class="cpe-ft__nav">
                    <a href="https://alfaejabrasil.org.br/nossas-publicacoes" target="_blank" rel="noopener">Nossas Publicações</a>
                    <a href="https://alfaejabrasil.org.br/alfa-eja-brasil-o-que-e-e-quais-sao-seus-principais-objetivos" target="_blank" rel="noopener">O projeto ALFA-EJA Brasil</a>
                    <a href="https://alfaejabrasil.org.br/politica-de-privacidade" target="_blank" rel="noopener">Política de privacidade</a>
                    <a href="https://alfaejabrasil.org.br/termos-de-uso" target="_blank" rel="noopener">Termos de uso</a>
                    <a href="https://alfaejabrasil.org.br/contato" target="_blank" rel="noopener">Contato</a>
                </nav>
            </div>

            <hr class="cpe-ft__divider">

            {{-- Realização e Parceria --}}
            <div class="cpe-ft__partners">
                <div class="cpe-ft__partner">
                    <span class="cpe-ft__partner-label">Realização</span>
                    <img src="{{ asset('images/ipf-white.png') }}" alt="Instituto Paulo Freire" class="cpe-ft__partner-img">
                </div>
                <div class="cpe-ft__partner">
                    <span class="cpe-ft__partner-label">Parceria</span>
                    <img src="{{ asset('images/petrobras-white.png') }}" alt="Petrobras" class="cpe-ft__partner-img">
                </div>
            </div>

            <hr class="cpe-ft__divider">

            <p class="cpe-ft__legal">INSTITUTO DE EDUCAÇÃO E DIREITOS HUMANOS PAULO FREIRE | CNPJ 04.950.603/0001-05</p>
        </div>
    </div>
</footer>

<style>
    .cpe-ft {
        margin-top: auto;
        font-family: 'Inter', 'Montserrat', system-ui, sans-serif;
    }

    .cpe-ft__inner {
        width: min(100%, 1272px);
        margin: 0 auto;
        padding: 0 44px;
    }

    /* ---------- Faixa rosa ---------- */
    .cpe-ft__social-band {
        background: #E41F6D;
        padding: 44px 0 56px;
    }

    .cpe-ft__social-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 35px;
        font-weight: 700;
        line-height: 1.2;
        text-align: center;
        color: #fff;
        margin: 0 0 24px;
    }

    .cpe-ft__social {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 24px 96px;
    }

    .cpe-ft__social-item {
        display: inline-flex;
        align-items: center;
        gap: 14px;
        color: #fff;
        text-decoration: none;
        font-family: 'Montserrat', sans-serif;
        font-size: 25px;
        font-weight: 500;
    }

    .cpe-ft__social-item img {
        display: block;
        height: 40px;
        width: auto;
    }

    .cpe-ft__social-item:hover {
        color: #fff;
        opacity: .85;
    }

    /* ---------- Faixa ondulada ---------- */
    .cpe-ft__wave {
        line-height: 0;
        /* Ondas no SVG (viewBox 120): verde 3-29%, magenta 30-38%, amarelo 38-53%, azul 53-91%.
           A emenda fica logo abaixo do verde/magenta: verde no rosa, amarelo e azul no roxo. */
        background: linear-gradient(#E41F6D 40%, #421944 40%);
        position: relative;
        z-index: 1;
    }

    .cpe-ft__wave img {
        display: block;
        width: 100%;
        height: auto;
    }

    /* ---------- Rodapé roxo ---------- */
    .cpe-ft__main {
        background: #421944;
        color: #fff;
        padding: 40px 0 56px;
        position: relative;
    }

    .cpe-ft__top {
        display: flex;
        justify-content: space-between;
        gap: 48px;
        flex-wrap: wrap;
        padding: 24px 0 40px;
    }

    .cpe-ft__brand {
        display: flex;
        flex-direction: column;
        gap: 24px;
        max-width: 384px;
    }

    .cpe-ft__brand-lockup {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .cpe-ft__brand-icon {
        height: 68px;
        width: auto;
    }

    .cpe-ft__brand-name {
        font-family: 'Montserrat', sans-serif;
        font-size: 34px;
        font-weight: 700;
        line-height: .88;
        color: #fff;
    }

    .cpe-ft__brand-tag {
        font-size: 20px;
        font-weight: 400;
        line-height: 1.2;
        color: #fff;
        margin: 0;
    }

    .cpe-ft__brand-cartas {
        height: 40px;
        width: auto;
        align-self: flex-start; /* evita esticar na coluna flex */
        margin-top: 12px;
    }

    .cpe-ft__nav {
        display: flex;
        flex-direction: column;
        gap: 23px;
    }

    .cpe-ft__nav a {
        color: #fff;
        text-decoration: none;
        font-size: 23px;
        font-weight: 400;
        line-height: 1.2;
    }

    .cpe-ft__nav a:hover {
        color: #fff;
        text-decoration: underline;
    }

    .cpe-ft__divider {
        border: 0;
        border-top: 1px solid #4D4D4D;
        margin: 0;
    }

    .cpe-ft__partners {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        gap: 150px;
        flex-wrap: wrap;
        padding: 40px 0;
    }

    .cpe-ft__partner {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 31px;
    }

    .cpe-ft__partner-label {
        font-size: 20px;
        font-weight: 400;
        color: #D39EDB;
    }

    .cpe-ft__partner-img {
        height: 46px;
        width: auto;
        object-fit: contain;
    }

    .cpe-ft__legal {
        text-align: center;
        font-size: 18px;
        font-weight: 400;
        line-height: 1.2;
        color: #fff;
        margin: 0;
        padding-top: 32px;
    }

    @media (max-width: 900px) {
        .cpe-ft__inner {
            padding: 0 22px;
        }

        .cpe-ft__social-title {
            font-size: 26px;
        }

        .cpe-ft__social-item {
            font-size: 20px;
        }

        .cpe-ft__top {
            gap: 36px;
        }

        .cpe-ft__nav a {
            font-size: 19px;
        }

        .cpe-ft__partners {
            gap: 48px;
        }

        .cpe-ft__legal {
            font-size: 15px;
        }
    }
</style>
