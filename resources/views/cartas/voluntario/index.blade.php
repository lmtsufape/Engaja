@extends('cartas.layouts.app')

@section('title', 'Suas cartas - Cartas para Esperançar')

@section('body')
    @include('cartas.shared._styles')

    <main class="cpe-page cpe-volunteer">
        @include('cartas.shared._logo')

        <section class="cpe-volunteer__content">
            <h1>Suas cartas</h1>

            @if ($errors->any())
                <div class="cpe-alert cpe-alert--error">{{ $errors->first() }}</div>
            @endif

            @if (session('status'))
                <div class="cpe-alert">{{ session('status') }}</div>
            @endif

            @if($cartas->isEmpty())
                <div class="cpe-empty-card">
                    <div class="cpe-empty-frame">
                        <span class="cpe-empty-corner cpe-empty-corner--tl"></span>
                        <span class="cpe-empty-corner cpe-empty-corner--tr"></span>
                        <span class="cpe-empty-corner cpe-empty-corner--bl"></span>
                        <span class="cpe-empty-corner cpe-empty-corner--br"></span>
                        <strong>Você ainda não recebeu nenhuma carta</strong>
                        <span class="cpe-empty-hint">Assim que alguém enviar uma carta para você, enviaremos uma notificação para seu e-mail cadastrado.</span>
                    </div>
                </div>
            @else
                <div class="cpe-envelope-grid" role="region" aria-label="Suas cartas">
                    @foreach($cartas as $carta)
                        @php
                            $primeira = $carta->mensagens->sortBy('rodada')->first();
                            $selo = match ($carta->status) {
                                'aguardando_voluntario' => ['img' => 'selo-recebida.png', 'alt' => 'Carta recebida', 'tipo' => 'selo'],
                                'aguardando_verificacao' => ['img' => 'selo-enviada.png', 'alt' => 'Carta enviada', 'tipo' => 'selo'],
                                'aguardando_ajuste' => ['img' => 'carimbo-ajuste.png', 'alt' => 'Ajuste solicitado', 'tipo' => 'carimbo'],
                                default => ['img' => 'selo-respondida.png', 'alt' => 'Carta respondida', 'tipo' => 'selo'],
                            };
                            $dataCarta = optional($primeira?->created_at ?? $carta->created_at)->format('d/m/Y');
                        @endphp
                        <article class="cpe-envelope">
                            <img class="cpe-envelope__bg" src="{{ asset('images/cartas/envelope.png') }}" alt="" aria-hidden="true">
                            <img class="cpe-envelope__selo cpe-envelope__selo--{{ $selo['tipo'] }}" src="{{ asset('images/cartas/'.$selo['img']) }}" alt="{{ $selo['alt'] }}">
                            <a class="cpe-envelope__open" href="{{ route('cartas.cartas.show', $carta) }}" aria-label="Abrir carta de {{ $carta->educando?->nome ?? 'Remetente' }}">
                                <img src="{{ asset('images/cartas/botao-abrir-carta.png') }}" alt="Abrir carta">
                                @if(($carta->mensagens_nao_lidas_count ?? 0) > 0)
                                    <span class="cpe-unread-badge cpe-envelope__unread" aria-label="{{ $carta->mensagens_nao_lidas_count }} mensagem não lida">{{ $carta->mensagens_nao_lidas_count }}</span>
                                @endif
                            </a>
                            <div class="cpe-envelope__meta">
                                <div class="cpe-envelope__party">
                                    <span class="cpe-envelope__line" title="De: {{ $carta->educando?->nome ?? 'Remetente' }}">De: {{ $carta->educando?->nome ?? 'Remetente' }}</span>
                                    <span class="cpe-envelope__line" title="Para: {{ Auth::user()->name }}">Para: {{ Auth::user()->name }}</span>
                                </div>
                                <span class="cpe-envelope__date">{{ $dataCarta }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        @include('cartas.shared._user-menu')

        @if(session('cartas_thanks'))
            <div class="cpe-modal is-open" id="thanksModal">
                <div class="cpe-modal__backdrop"></div>
                <div class="cpe-modal__dialog">
                    <div class="cpe-thanks-brand">
                        <img src="{{ asset('images/cartas/cartas-logo.png') }}" alt="Cartas para Esperançar">
                    </div>
                    <h2>Muito obrigado.</h2>
                    <p>Cada carta enviada é mais do que uma correspondência. É um encontro entre pessoas, histórias e saberes. Obrigado(a) por contribuir para esta rede de diálogo, respeito e esperança construída pelo Projeto ALFA-EJA Brasil.</p>
                    <button type="button" class="cpe-button cpe-button--ghost" style="width:100%;" data-modal-close>Fechar</button>
                </div>
            </div>
        @endif
        @include('cartas.shared._help_fab')
    </main>

    @include('cartas.shared._scripts')

    <style>
        .cpe-volunteer {
            padding: 0 28px 56px;
            display: flex;
            flex-direction: column;
        }

        .cpe-volunteer__content {
            width: min(100%, 1200px);
            margin: 90px auto 0;
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 34px;
        }

        .cpe-unread-badge {
            min-width: 18px;
            height: 18px;
            border-radius: 999px;
            background: #e83b66;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            font-size: 10px;
            font-weight: 800;
            line-height: 1;
        }

        /* --- Lista estática de cartas (até 3 cartas por fileira) --- */
        .cpe-envelope-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: flex-start;
            gap: 32px 20px;
            width: 100%;
            margin: 0 auto;
        }

        .cpe-envelope {
            position: relative;
            width: min(100%, 360px);
            aspect-ratio: 606 / 326;
            container-type: inline-size;
            filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.12));
            transition: transform .25s ease, filter .25s ease;
        }

        .cpe-envelope:hover {
            transform: translateY(-4px);
            filter: drop-shadow(0 12px 22px rgba(0, 0, 0, 0.18));
        }

        .cpe-envelope__bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            pointer-events: none;
            user-select: none;
        }

        .cpe-envelope__selo {
            position: absolute;
            top: 28%;
            left: 7%;
            width: 21%;
            transform: rotate(-16.23deg);
            transform-origin: center;
            pointer-events: none;
            user-select: none;
        }

        .cpe-envelope__selo--carimbo {
            top: 1%;
            left: 50%;
            width: 40%;
            transform: translateX(-50%) rotate(-4deg);
        }

        .cpe-envelope__open {
            position: absolute;
            left: 50%;
            top: 52%;
            transform: translate(-50%, -50%);
            width: 13.5%;
            display: block;
            transition: transform .2s ease;
        }

        .cpe-envelope__open img {
            display: block;
            width: 100%;
            height: auto;
        }

        .cpe-envelope__open:hover,
        .cpe-envelope__open:focus-visible {
            transform: translate(-50%, -50%) scale(1.08);
            outline: none;
        }

        .cpe-envelope__unread {
            position: absolute;
            top: -6px;
            right: -6px;
        }

        .cpe-envelope__meta {
            position: absolute;
            left: 8%;
            right: 11%;
            bottom: 13%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 8px;
            color: #008BBC;
            font-weight: 500;
            line-height: 1.25;
            overflow: hidden;
        }

        .cpe-envelope__party {
            font-size: clamp(11px, 2.64cqi, 16px);
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1;
            overflow: hidden;
        }

        .cpe-envelope__line {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            max-width: 100%;
        }

        .cpe-envelope__date {
            font-size: clamp(11px, 2.64cqi, 16px);
            text-align: right;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .cpe-volunteer h1 {
            margin: 0 0 20px;
            text-align: center;
            font-size: 32px;
            font-weight: 600;
            line-height: 1.2;
        }

        .cpe-empty-card {
            width: min(100%, 432px);
            margin: 0 auto;
            background: rgba(150, 2, 199, .05);
            border-radius: 8px;
            padding: 14px;
            display: grid;
            place-items: center;
        }

        .cpe-empty-frame {
            position: relative;
            width: 100%;
            padding: 24px 20px;
            display: grid;
            justify-items: center;
            gap: 8px;
        }

        .cpe-empty-corner {
            position: absolute;
            width: 40px;
            height: 40px;
        }

        .cpe-empty-corner--tl {
            top: 0;
            left: 0;
            border-top: 1px solid var(--cpe-purple);
            border-left: 1px solid var(--cpe-purple);
        }

        .cpe-empty-corner--tr {
            top: 0;
            right: 0;
            border-top: 1px solid var(--cpe-purple);
            border-right: 1px solid var(--cpe-purple);
        }

        .cpe-empty-corner--bl {
            bottom: 0;
            left: 0;
            border-bottom: 1px solid var(--cpe-purple);
            border-left: 1px solid var(--cpe-purple);
        }

        .cpe-empty-corner--br {
            bottom: 0;
            right: 0;
            border-bottom: 1px solid var(--cpe-purple);
            border-right: 1px solid var(--cpe-purple);
        }

        .cpe-empty-frame strong {
            display: block;
            width: 100%;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.2;
            text-align: center;
            text-transform: uppercase;
            color: var(--cpe-purple);
        }

        .cpe-empty-hint {
            display: block;
            width: min(100%, 274px);
            color: #414652;
            font-size: 14px;
            line-height: 1.2;
            text-align: center;
        }

        .cpe-modal-actions--three {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .cpe-file-placeholder {
            height: 100%;
            min-height: 330px;
            display: grid;
            place-items: center;
            color: rgba(0, 0, 0, .45);
            font-weight: 700;
        }

        .cpe-upload--compact {
            min-height: 74px;
            margin-top: 10px;
        }

        .cpe-thanks-brand {
            height: 100px;
            border-radius: 7px;
            background: #fff;
            display: grid;
            place-items: center;
            margin-bottom: 24px;
        }

        .cpe-thanks-brand img {
            width: 150px;
        }

        @media (max-width: 720px) {
            .cpe-volunteer__content {
                margin-top: 60px;
            }

            .cpe-envelope-grid {
                gap: 20px;
            }

            .cpe-modal-actions--three {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
