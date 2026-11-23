{{-- Botão flutuante de ajuda — Cartas para Esperançar --}}
<div class="cpe-help-fab" id="helpFab">
    <button
        type="button"
        class="cpe-help-fab__btn"
        id="helpFabToggle"
        aria-label="Precisa de ajuda?"
        aria-expanded="false"
        aria-controls="helpFabPopup"
    >
        <svg class="cpe-help-fab__icon" width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="1.8"/>
            <path d="M9 9C9 7.34315 10.3431 6 12 6C13.6569 6 15 7.34315 15 9C15 10.3062 14.1652 11.4175 13 11.8293V13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <circle cx="12" cy="16.5" r="1" fill="currentColor"/>
        </svg>
    </button>

    <div class="cpe-help-fab__popup" id="helpFabPopup" role="dialog" aria-label="Informações de ajuda">
        <div class="cpe-help-fab__header">
            <span class="cpe-help-fab__title">Precisa de ajuda?</span>
            <button type="button" class="cpe-help-fab__close" id="helpFabClose" aria-label="Fechar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <p class="cpe-help-fab__copy">Entre em contato com nosso time de suporte pelo canal que preferir:</p>

        <a href="mailto:douglas.batista-me@alfaejabrasil.org.br" class="cpe-help-fab__link">
            <span class="cpe-help-fab__link-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="2" y="4" width="20" height="16" rx="3" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M2 7l10 6 10-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="cpe-help-fab__link-body">
                <span class="cpe-help-fab__link-label">E-mail</span>
                <span class="cpe-help-fab__link-value">douglas.batista-me@alfaejabrasil.org.br</span>
            </span>
        </a>

        <a href="https://wa.me/5511978605213" target="_blank" rel="noopener" class="cpe-help-fab__link cpe-help-fab__link--whatsapp">
            <span class="cpe-help-fab__link-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6.014 8.006a7.63 7.63 0 0 0-.344 2.275c0 1.38.37 2.68 1.012 3.808L5 20l5.997-1.632A7.953 7.953 0 0 0 14.7 19.6a7.7 7.7 0 0 0 2.3-.35 7.7 7.7 0 0 0 4.6-4.6A7.7 7.7 0 0 0 22 12.35v-.7a7.7 7.7 0 0 0-.35-2.3 7.7 7.7 0 0 0-4.6-4.6A7.7 7.7 0 0 0 14.75 4.4h-.7a7.7 7.7 0 0 0-2.3.35 7.7 7.7 0 0 0-4.6 4.6 7.603 7.603 0 0 0-1.136-1.344Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9 10c.5-1 1.5-1 2 0 .2.4.3.7 0 1-.5.5-1 1-.5 1.5s1.5 1.5 2 1.5.5-.5 1-1c.4-.4 1-.4 1.5 0l1 1c.4.5 0 1.5-.5 2s-1.5.5-2.5 0-2.5-1.5-3.5-3-.5-2.5 0-2.5Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="cpe-help-fab__link-body">
                <span class="cpe-help-fab__link-label">WhatsApp</span>
                <span class="cpe-help-fab__link-value">+55 11 97860-5213</span>
            </span>
        </a>
    </div>
</div>

<style>
    /* ── Floating Action Button ── */
    .cpe-help-fab {
        position: fixed;
        bottom: 28px;
        right: 28px;
        z-index: 2500;
        font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    }

    .cpe-help-fab__btn {
        width: 56px;
        height: 56px;
        border: 0;
        border-radius: 50%;
        background: var(--cpe-purple, #a900d9);
        color: #fff;
        cursor: pointer;
        display: grid;
        place-items: center;
        box-shadow:
            0 4px 14px rgba(169, 0, 217, .32),
            0 1px 3px rgba(0, 0, 0, .12);
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .cpe-help-fab__btn:hover,
    .cpe-help-fab__btn:focus-visible {
        transform: scale(1.08);
        box-shadow:
            0 6px 20px rgba(169, 0, 217, .4),
            0 2px 6px rgba(0, 0, 0, .14);
        outline: none;
    }

    .cpe-help-fab__btn:active {
        transform: scale(.96);
    }

    .cpe-help-fab__icon {
        transition: transform .25s ease;
    }

    .cpe-help-fab.is-open .cpe-help-fab__btn {
        background: #9600c7;
    }

    .cpe-help-fab.is-open .cpe-help-fab__icon {
        transform: rotate(90deg);
    }

    /* ── Popup Card ── */
    .cpe-help-fab__popup {
        position: absolute;
        bottom: calc(100% + 14px);
        right: 0;
        width: 400px;
        background: #fff;
        border-radius: 14px;
        padding: 22px;
        box-shadow:
            0 12px 40px rgba(0, 0, 0, .16),
            0 2px 8px rgba(0, 0, 0, .08);
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px) scale(.96);
        transform-origin: bottom right;
        transition:
            opacity .22s ease,
            visibility .22s ease,
            transform .22s cubic-bezier(.22, .61, .36, 1);
        pointer-events: none;
    }

    .cpe-help-fab.is-open .cpe-help-fab__popup {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }

    .cpe-help-fab__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .cpe-help-fab__title {
        font-size: 16px;
        font-weight: 800;
        color: #111;
    }

    .cpe-help-fab__close {
        width: 30px;
        height: 30px;
        border: 0;
        border-radius: 50%;
        background: #f3f0ed;
        color: #555;
        cursor: pointer;
        display: grid;
        place-items: center;
        transition: background .15s ease, color .15s ease;
    }

    .cpe-help-fab__close:hover,
    .cpe-help-fab__close:focus-visible {
        background: #e8e4e0;
        color: #111;
        outline: none;
    }

    .cpe-help-fab__copy {
        font-size: 13px;
        line-height: 1.45;
        color: #555;
        margin: 0 0 16px;
    }

    .cpe-help-fab__copy strong {
        color: #222;
    }

    /* ── Contact Links ── */
    .cpe-help-fab__link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 10px;
        background: #f8f6f4;
        text-decoration: none;
        color: #333;
        transition: background .15s ease, box-shadow .15s ease;
    }

    .cpe-help-fab__link + .cpe-help-fab__link {
        margin-top: 8px;
    }

    .cpe-help-fab__link:hover,
    .cpe-help-fab__link:focus-visible {
        background: #f0ece8;
        box-shadow: 0 0 0 2px rgba(169, 0, 217, .15);
        outline: none;
    }

    .cpe-help-fab__link--whatsapp:hover,
    .cpe-help-fab__link--whatsapp:focus-visible {
        background: #edf7ee;
        box-shadow: 0 0 0 2px rgba(37, 211, 102, .2);
    }

    .cpe-help-fab__link-icon {
        flex-shrink: 0;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #fff;
        display: grid;
        place-items: center;
        color: var(--cpe-purple, #a900d9);
        box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
    }

    .cpe-help-fab__link--whatsapp .cpe-help-fab__link-icon {
        color: #25d366;
    }

    .cpe-help-fab__link-body {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    .cpe-help-fab__link-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #888;
    }

    .cpe-help-fab__link-value {
        font-size: 13px;
        font-weight: 600;
        color: #222;
        word-break: break-all;
    }

    /* ── Responsive ── */
    @media (max-width: 480px) {
        .cpe-help-fab {
            bottom: 18px;
            right: 18px;
        }

        .cpe-help-fab__popup {
            width: calc(100vw - 36px);
            right: -10px;
        }

        .cpe-help-fab__btn {
            width: 50px;
            height: 50px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const fab = document.getElementById('helpFab');
        const toggle = document.getElementById('helpFabToggle');
        const close = document.getElementById('helpFabClose');
        if (!fab || !toggle) return;

        const abrir = () => {
            fab.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        };

        const fechar = () => {
            fab.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', () => {
            fab.classList.contains('is-open') ? fechar() : abrir();
        });

        if (close) {
            close.addEventListener('click', fechar);
        }

        // Fecha ao clicar fora
        document.addEventListener('click', (e) => {
            if (!fab.contains(e.target)) {
                fechar();
            }
        });

        // Fecha com Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && fab.classList.contains('is-open')) {
                fechar();
                toggle.focus();
            }
        });
    });
</script>
