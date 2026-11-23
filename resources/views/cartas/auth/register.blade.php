@extends('cartas.auth._shell')

@section('title', 'Criar conta - Cartas para Esperançar')
@section('auth-bg-style', "background-image: url('" . asset('images/cartas/bg-cadastro.png') . "');")

@section('auth-content')
    <h1 class="cartas-title">Crie sua conta</h1>

    @if ($errors->any())
        <div id="registrationErrors" class="cartas-alert cartas-alert--error" role="alert" tabindex="-1">
            <strong>Revise os campos destacados:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('cartas.register.store') }}" class="cartas-form" data-estados-url="{{ route('cartas.localidades.estados') }}" data-municipios-url="{{ route('cartas.localidades.municipios', ['estadoId' => '__ESTADO__']) }}">
        @csrf

        <div class="cartas-form-group">
            <label class="cartas-label" for="name">Nome</label>
            <input id="name" class="cartas-field-light @error('name') is-invalid @enderror" type="text" name="name" value="{{ old('name') }}" placeholder="Digite seu nome completo, sem abreviação." aria-describedby="name-error" required autofocus>
            @error('name')
                <p id="name-error" class="cartas-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="cartas-form-group">
            <label class="cartas-label" for="email">E-mail</label>
            <input id="email" class="cartas-field-light @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="Digite seu e-mail." aria-describedby="email-error" required>
            @error('email')
                <p id="email-error" class="cartas-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="cartas-form-group">
            <label class="cartas-label" for="passwordInput">Senha</label>
            <div class="cartas-password-wrap">
                <input id="passwordInput" class="cartas-field-light @error('password') is-invalid @enderror" type="password" name="password" placeholder="Digite uma senha." aria-describedby="password-error" required>
                <button type="button" class="cartas-password-toggle" onclick="togglePassword('passwordInput', this)" title="Mostrar/Ocultar senha">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            @error('password')
                <p id="password-error" class="cartas-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="cartas-form-group">
            <label class="cartas-label" for="passwordConfirmInput">Confirmar senha</label>
            <div class="cartas-password-wrap">
                <input id="passwordConfirmInput" class="cartas-field-light @error('password') is-invalid @enderror" type="password" name="password_confirmation" placeholder="Confirmar senha." required>
                <button type="button" class="cartas-password-toggle" onclick="togglePassword('passwordConfirmInput', this)" title="Mostrar/Ocultar senha">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <div class="cartas-section">
            <div class="cartas-grid-2">
                <div class="cartas-form-group">
                    <label class="cartas-label" for="cpf">CPF</label>
                    <input id="cpf" class="cartas-field-light @error('cpf') is-invalid @enderror" type="text" name="cpf" value="{{ old('cpf') }}" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" aria-describedby="cpf-error" required>
                    @error('cpf')
                        <p id="cpf-error" class="cartas-field-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="cartas-form-group">
                    <label class="cartas-label" for="telefone">Telefone</label>
                    <input id="telefone" class="cartas-field-light @error('telefone') is-invalid @enderror" type="text" name="telefone" value="{{ old('telefone') }}" inputmode="numeric" maxlength="15" placeholder="(99) 99999-9999" aria-describedby="telefone-error" required>
                    @error('telefone')
                        <p id="telefone-error" class="cartas-field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="cartas-grid-2">
                <div class="cartas-form-group">
                    <label class="cartas-label" for="estado_id">Estado</label>
                    <select id="estado_id" class="cartas-field-light @error('estado_id') is-invalid @enderror" name="estado_id" aria-describedby="estado-error" required>
                        <option value="">Selecione...</option>
                    </select>
                    @error('estado_id')
                        <p id="estado-error" class="cartas-field-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="cartas-form-group" id="municipio-wrap" hidden>
                    <label class="cartas-label" for="municipio_id">Município</label>
                    <select id="municipio_id" class="cartas-field-light @error('municipio_id') is-invalid @enderror" name="municipio_id" aria-describedby="municipio-error" disabled required>
                        <option value="">Selecione...</option>
                    </select>
                    @error('municipio_id')
                        <p id="municipio-error" class="cartas-field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="cartas-form-group">
            <label class="cartas-label">Você é funcionário da Petrobras?</label>
            <div class="cartas-radio-group">
                <label class="cartas-radio">
                    <input type="radio" name="cartas_tipo_vinculo" value="{{ \App\Models\User::VINCULO_PETROBRAS }}" @checked(old('cartas_tipo_vinculo') === \App\Models\User::VINCULO_PETROBRAS) required>
                    <span>Sim</span>
                </label>
                <label class="cartas-radio">
                    <input type="radio" name="cartas_tipo_vinculo" value="{{ \App\Models\User::VINCULO_COMUNIDADE }}" @checked(old('cartas_tipo_vinculo') === \App\Models\User::VINCULO_COMUNIDADE)>
                    <span>Não</span>
                </label>
            </div>
            @error('cartas_tipo_vinculo')
                <p class="cartas-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="cartas-form-group">
            <label class="cartas-label">Até quantas cartas você poderá escrever em resposta aos(às) educandos(as) participantes da ação Cartas para Esperançar?</label>
            <div class="cartas-radio-group cartas-radio-group--inline">
                @for ($i = 1; $i <= 5; $i++)
                    <label class="cartas-radio">
                        <input type="radio" name="cartas_limite_respostas" value="{{ $i }}" @checked(old('cartas_limite_respostas') == $i) required>
                        <span>{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</span>
                    </label>
                @endfor
            </div>
            @error('cartas_limite_respostas')
                <p class="cartas-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="cartas-terms-check @error('termos_aceitos') cartas-terms-check--invalid @enderror">
            <label class="cartas-terms-check__label">
                <input type="checkbox" name="termos_aceitos" id="termosCheckbox" value="1" {{ old('termos_aceitos') ? 'checked' : '' }} required>
                <span>Li e estou de acordo com os <a href="#" id="openTermsModal" class="cartas-terms-check__link">termos de uso</a>.</span>
            </label>
            @error('termos_aceitos')
                <p class="cartas-field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="cartas-button" id="submitBtn">Continuar</button>
    </form>

    <a href="{{ route('cartas.login') }}" class="cartas-link" style="margin-top:28px;font-weight:700;">Já tenho uma conta</a>

    <script>
        const togglePassword = (inputId, btn) => {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        };

        const onlyDigits = value => (value || '').replace(/\D+/g, '');
        const maskCpf = value => {
            const digits = onlyDigits(value).slice(0, 11);
            return digits.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        };
        const maskPhone = value => {
            const digits = onlyDigits(value).slice(0, 11);
            if (digits.length <= 10) return digits.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2');
            return digits.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2');
        };

        const cpf = document.getElementById('cpf');
        const telefone = document.getElementById('telefone');
        cpf?.addEventListener('input', event => event.target.value = maskCpf(event.target.value));
        telefone?.addEventListener('input', event => event.target.value = maskPhone(event.target.value));
        if (cpf) cpf.value = maskCpf(cpf.value);
        if (telefone) telefone.value = maskPhone(telefone.value);

        const form = document.querySelector('.cartas-form');
        const estado = document.getElementById('estado_id');
        const municipio = document.getElementById('municipio_id');
        const municipioWrap = document.getElementById('municipio-wrap');
        const submitButton = form.querySelector('button[type="submit"]');
        const estadoSelecionado = @json(old('estado_id'));
        const municipioSelecionado = @json(old('municipio_id'));
        let municipiosRequestId = 0;

        document.getElementById('registrationErrors')?.focus();

        const addOption = (select, value, label) => select.add(new Option(label, value));
        const setSubmitLoading = loading => {
            submitButton.disabled = loading;
            submitButton.setAttribute('aria-busy', loading ? 'true' : 'false');
        };
        const limparMunicipios = (label = 'Selecione seu município', mostrar = false) => {
            municipio.dataset.loading = 'false';
            municipio.replaceChildren(new Option(label, ''));
            municipio.disabled = true;
            municipioWrap.hidden = !mostrar;
            setSubmitLoading(false);
        };
        const carregarMunicipiosLoading = () => {
            municipio.dataset.loading = 'true';
            municipio.replaceChildren(new Option('Carregando municípios...', ''));
            municipio.disabled = true;
            municipioWrap.hidden = false;
            setSubmitLoading(true);
        };
        const carregarMunicipios = async (estadoId, selecionado = null) => {
            const requestId = ++municipiosRequestId;
            limparMunicipios();
            if (!estadoId) return;
            carregarMunicipiosLoading();
            try {
                const url = form.dataset.municipiosUrl.replace('__ESTADO__', estadoId);
                const resposta = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!resposta.ok) throw new Error();
                const municipios = await resposta.json();
                if (requestId !== municipiosRequestId) return;

                municipio.dataset.loading = 'false';
                municipio.replaceChildren(new Option('Selecione seu município', ''));
                municipios.forEach(item => addOption(municipio, item.id, item.nome));
                municipio.disabled = false;
                municipioWrap.hidden = false;
                setSubmitLoading(false);
                if (selecionado) municipio.value = String(selecionado);
            } catch (_) {
                if (requestId !== municipiosRequestId) return;
                limparMunicipios('Não foi possível carregar os municípios', true);
            }
        };

        estado.addEventListener('change', () => carregarMunicipios(estado.value));
        form.addEventListener('submit', event => {
            if (municipio.dataset.loading === 'true') {
                event.preventDefault();
                municipio.setCustomValidity('Aguarde o carregamento dos municípios.');
                municipio.reportValidity();
            } else {
                municipio.setCustomValidity('');
            }
        });

        fetch(form.dataset.estadosUrl, { headers: { Accept: 'application/json' } })
            .then(resposta => resposta.ok ? resposta.json() : Promise.reject())
            .then(estados => {
                estados.forEach(item => addOption(estado, item.id, `${item.nome} (${item.sigla})`));
                if (estadoSelecionado) {
                    estado.value = String(estadoSelecionado);
                    return carregarMunicipios(estado.value, municipioSelecionado);
                }
            })
            .catch(() => {
                addOption(estado, '', 'Não foi possível carregar os estados');
            });
    </script>
    {{-- Modal dos termos --}}
    <div class="cartas-terms-modal" id="termsModal" role="dialog" aria-modal="true" aria-labelledby="termsModalTitle">
        <div class="cartas-terms-modal__backdrop"></div>
        <div class="cartas-terms-modal__dialog">
            <div class="cartas-terms-modal__brand">
                <img src="{{ asset('images/cartas/cartas-logo.png') }}" alt="Cartas para Esperançar">
            </div>

            <h1 id="termsModalTitle">Termos de uso</h1>

            <div class="cartas-terms-modal__content">
                <p><strong>1. Ciência sobre acesso aos conteúdos</strong></p>
                <p>Estou ciente de que as cartas, imagens, documentos e demais conteúdos inseridos nesta plataforma serão acessados exclusivamente pelas equipes responsáveis pela gestão da ação, incluindo profissionais autorizados do Projeto ALFA-EJA Brasil, do Instituto Paulo Freire, da Petrobras e da equipe técnica responsável pela administração e suporte da plataforma.</p>
                <p>Esse acesso ocorrerá para fins de organização, distribuição das correspondências, acompanhamento pedagógico, curadoria, monitoramento, documentação e avaliação da ação.</p>

                <p><strong>2. Autorização de uso da obra intelectual</strong></p>
                <p>Autorizo, de forma gratuita e sem exclusividade, a utilização total ou parcial dos conteúdos produzidos por mim no âmbito da ação Cartas para Esperançar, incluindo textos, cartas, desenhos, fotografias, ilustrações, poemas, relatos, depoimentos e demais produções autorais.</p>
                <p>A autorização compreende o uso para fins educativos, comunicacionais, institucionais, científicos, culturais e de memória do projeto, em materiais impressos e digitais.</p>

                <p><strong>3. Responsabilidade sobre o conteúdo enviado</strong></p>
                <p>Declaro que sou responsável pelas informações, imagens e produções que inserir na plataforma e que não incluirei conteúdos ofensivos, discriminatórios ou que violem direitos de terceiros.</p>

                <p><strong>4. Consentimento</strong></p>
                <p>Ao prosseguir, confirmo que li e estou de acordo com estes termos de participação.</p>
            </div>

            <button type="button" class="cartas-button" id="closeTermsModal" style="margin-top:12px;">Fechar</button>
        </div>
    </div>
    @include('cartas.shared._help_fab')
@endsection

@push('styles')
    <style>
        .cartas-form-group {
            margin-bottom: 18px;
            width: 100%;
        }

        .cartas-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #111;
            margin-bottom: 6px;
        }

        .cartas-password-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .cartas-password-toggle {
            position: absolute;
            right: 12px;
            background: transparent;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cartas-password-toggle:hover {
            color: #111;
        }

        .cartas-field-light {
            width: 100%;
            height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #fff;
            padding: 0 14px;
            font-size: 15px;
            font-weight: 500;
            outline: none;
            color: #111;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .cartas-field-light::placeholder {
            color: #111;
            opacity: 1;
        }

        select.cartas-field-light {
            padding-right: 36px;
            appearance: auto; /* Ensure dropdown arrow is visible */
            color: #111;
            cursor: pointer;
        }

        select.cartas-field-light option {
            color: #111;
        }

        .cartas-field-light:focus {
            border-color: var(--cartas-purple);
            box-shadow: 0 0 0 3px rgba(168, 0, 214, .14);
        }

        .cartas-field-light.is-invalid {
            border-color: #c62828;
            box-shadow: 0 0 0 3px rgba(198, 40, 40, .14);
        }

        .cartas-radio-group {
            display: flex;
            gap: 24px;
        }

        .cartas-radio-group--inline {
            flex-wrap: wrap;
        }

        .cartas-radio {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            font-size: 15px;
            color: #333;
            font-weight: 500;
        }

        .cartas-radio input[type="radio"] {
            margin: 0 8px 0 0;
            width: 18px;
            height: 18px;
            accent-color: var(--cartas-purple);
            cursor: pointer;
        }

        .cartas-field-error {
            margin: 6px 0 0;
            color: #b42318;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.35;
        }

        .cartas-alert--error ul {
            margin: 8px 0 0;
            padding-left: 20px;
        }

        .cartas-field-light:disabled,
        input.cartas-field-light:read-only,
        textarea.cartas-field-light:read-only {
            background: #f8fafc;
            color: #111;
            cursor: not-allowed;
            border-color: #cbd5e1;
        }

        .cartas-section {
            background: transparent;
            padding: 0;
            margin: 0;
        }

        .cartas-grid-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0 16px;
        }

        @media (min-width: 480px) {
            .cartas-grid-2 {
                grid-template-columns: 1fr 1fr;
            }
        }
        .cartas-terms-check {
            width: 100%;
            margin: 4px 0 8px;
        }

        .cartas-terms-check__label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            line-height: 1.4;
        }

        .cartas-terms-check__label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            min-width: 18px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: #a800d6;
        }

        .cartas-terms-check--invalid {
            padding: 10px;
            border: 1px solid #c62828;
            border-radius: 6px;
            background: #fff4f4;
        }

        .cartas-terms-check__link {
            color: #a800d6;
            text-decoration: underline;
            font-weight: 700;
        }

        .cartas-terms-check__link:hover {
            color: #8000a5;
        }

        .cartas-button:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .cartas-terms-modal {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .cartas-terms-modal.is-open {
            display: flex;
        }

        .cartas-terms-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, .68);
            backdrop-filter: blur(7px);
        }

        .cartas-terms-modal__dialog {
            position: relative;
            width: min(100%, 650px);
            max-height: min(700px, calc(100vh - 48px));
            display: flex;
            flex-direction: column;
            border-radius: 8px;
            background: #f1eeeb;
            padding: 24px;
            box-shadow: 0 22px 60px rgba(0, 0, 0, .32);
            color: #111;
        }

        .cartas-terms-modal__brand {
            height: 72px;
            border-radius: 6px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }

        .cartas-terms-modal__brand img {
            width: 112px;
            height: auto;
        }

        .cartas-terms-modal h1 {
            margin: 0 0 14px;
            font-size: 20px;
            line-height: 1.25;
            font-weight: 800;
        }

        .cartas-terms-modal__content {
            flex: 1;
            overflow-y: auto;
            min-height: 0;
            padding-right: 8px; /* space for scrollbar */
        }

        .cartas-terms-modal__content p {
            margin: 0 0 16px;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
        }

        .cartas-terms-modal__content strong {
            color: #000;
            font-size: 15px;
        }


    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const openBtn = document.getElementById('openTermsModal');
            const closeBtn = document.getElementById('closeTermsModal');
            const modal = document.getElementById('termsModal');
            const backdrop = modal?.querySelector('.cartas-terms-modal__backdrop');

            openBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                modal?.classList.add('is-open');
            });

            closeBtn?.addEventListener('click', () => {
                modal?.classList.remove('is-open');
            });

            backdrop?.addEventListener('click', () => {
                modal?.classList.remove('is-open');
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    modal?.classList.remove('is-open');
                }
            });
        });
    </script>
@endpush
