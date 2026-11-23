@auth
    @if (Auth::user()->isCartasUser() && Auth::user()->cartas_terms_accepted_at && Auth::user()->hasVerifiedEmail() && is_null(Auth::user()->cartas_welcome_seen_at))
        @php
            Auth::user()->forceFill(['cartas_welcome_seen_at' => now()])->save();
        @endphp
        <div class="cartas-welcome-modal is-open" id="cartasWelcomeModal" role="dialog" aria-modal="true" aria-labelledby="cartasWelcomeTitle">
            <div class="cartas-welcome-modal__backdrop" data-modal-close="cartasWelcomeModal"></div>
            <div class="cartas-welcome-modal__dialog">
                <div class="cartas-welcome-modal__brand">
                    <img src="{{ asset('images/cartas/cartas-logo.png') }}" alt="Cartas para Esperançar">
                </div>

                <h1 id="cartasWelcomeTitle">Bem-vindo(a)!</h1>

                <p>Você está prestes a participar da ação pedagógica Cartas para Esperançar, uma iniciativa do Projeto ALFA-EJA Brasil, realizado pelo Instituto Paulo Freire em parceria com a Petrobras.</p>

                <p>A proposta promove um encontro humano e dialógico entre educandos(as) da Educação de Jovens, Adultos e Idosos (EJA) dos 15 municípios participantes do Projeto e funcionários(as) voluntários(as) da Petrobras, por meio da troca de cartas. Inspirada nos princípios da Educação Popular e da dialogicidade defendida por Paulo Freire. A ação busca fortalecer vínculos, compartilhar experiências de vida, valorizar diferentes saberes e construir pontes de esperança entre pessoas de diferentes territórios e trajetórias.</p>

                <p>As cartas poderão ser escritas à mão ou produzidas por meio de outras formas de expressão, como desenhos, fotografias, colagens, poemas visuais, ilustrações ou linguagens artísticas diversas. Cada correspondência representa uma oportunidade de diálogo, escuta e reconhecimento mútuo.</p>

                <button type="button" class="cartas-welcome-modal__button" id="cartasWelcomeClose">Fechar</button>
            </div>
        </div>

        <style>
            .cartas-welcome-modal {
                position: fixed;
                inset: 0;
                z-index: 3000;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 24px;
            }

            .cartas-welcome-modal.is-open {
                display: flex;
            }

            .cartas-welcome-modal.is-hidden {
                display: none !important;
            }

            .cartas-welcome-modal__backdrop {
                position: absolute;
                inset: 0;
                background: rgba(0, 0, 0, .68);
                backdrop-filter: blur(7px);
            }

            .cartas-welcome-modal__dialog {
                position: relative;
                width: min(100%, 720px);
                max-height: calc(100vh - 48px);
                overflow-y: auto;
                border-radius: 10px;
                background: #f1eeeb;
                padding: 30px 34px;
                box-shadow: 0 24px 64px rgba(0, 0, 0, .36);
                color: #262626;
                font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            }

            .cartas-welcome-modal__brand {
                height: 80px;
                border-radius: 8px;
                background: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 22px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
            }

            .cartas-welcome-modal__brand img {
                width: 130px;
                height: auto;
            }

            .cartas-welcome-modal h1 {
                margin: 0 0 16px;
                font-size: 22px;
                line-height: 1.25;
                font-weight: 800;
                color: #111;
                font-family: 'Montserrat', sans-serif;
            }

            .cartas-welcome-modal p {
                margin: 0 0 15px;
                font-size: 14px;
                line-height: 1.6;
                color: #4a4a4a;
                font-family: 'Montserrat', sans-serif;
            }

            .cartas-welcome-modal p:last-of-type {
                margin-bottom: 24px;
            }

            .cartas-welcome-modal__button {
                width: 100%;
                height: 44px;
                border: 0;
                border-radius: 6px;
                background: #a800d6;
                color: #fff;
                font-size: 14.5px;
                font-weight: 700;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.15s ease;
                font-family: 'Montserrat', sans-serif;
                margin-top: 8px;
            }

            .cartas-welcome-modal__button:hover,
            .cartas-welcome-modal__button:focus {
                background: #9600c6;
                color: #fff;
                outline: none;
            }

            @media (max-width: 640px) {
                .cartas-welcome-modal__dialog {
                    padding: 20px 22px;
                }
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('cartasWelcomeModal');
                const closeBtn = document.getElementById('cartasWelcomeClose');
                const backdrop = modal?.querySelector('.cartas-welcome-modal__backdrop');

                const closeModal = () => {
                    if (!modal) return;
                    modal.classList.remove('is-open');
                    modal.classList.add('is-hidden');
                    modal.style.display = 'none';

                    fetch('{{ route('cartas.welcome.seen') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        }
                    }).catch(() => {});
                };

                closeBtn?.addEventListener('click', closeModal);
                backdrop?.addEventListener('click', closeModal);

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && modal && !modal.classList.contains('is-hidden') && modal.classList.contains('is-open')) {
                        closeModal();
                    }
                });
            });
        </script>
    @endif
@endauth
