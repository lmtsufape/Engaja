@extends('cartas.layouts.app')

@section('title', 'Cartas para Esperançar')

@section('body')
    <div class="cartas-dashboard">
        <header class="cartas-dashboard__header">
            <div class="cartas-dashboard__header-inner">
                <span class="cartas-dashboard__header-spacer" aria-hidden="true"></span>

                <a href="{{ route('cartas.dashboard') }}" aria-label="Voltar ao dashboard do Cartas">
                    <img src="{{ asset('images/cartas/cartas-logo.png') }}" alt="Cartas para Esperançar">
                </a>

                <div class="cartas-user-menu" id="cartasUserMenu">
                    <button class="cartas-user-menu__trigger" type="button" aria-expanded="false" aria-controls="cartasUserDropdown">
                        <span>{{ Auth::user()->name }}</span>
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div class="cartas-user-menu__dropdown" id="cartasUserDropdown">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="cartas-user-menu__item">Sair</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="cartas-dashboard__main" aria-label="Cartas para Esperançar"></main>
    </div>
@endsection

@push('styles')
    <style>
        .cartas-dashboard {
            flex: 1;
            background: #fff;
            position: relative;
        }

        .cartas-dashboard__header {
            height: 86px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 28px;
        }

        .cartas-dashboard__header-inner {
            width: 100%;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: 20px;
        }

        .cartas-dashboard__header img {
            width: 118px;
            height: auto;
        }

        .cartas-dashboard__header-spacer {
            min-width: 0;
        }

        .cartas-user-menu {
            position: relative;
            justify-self: end;
        }

        .cartas-user-menu__trigger {
            min-height: 38px;
            max-width: 260px;
            border: 1px solid rgba(0, 143, 189, .2);
            border-radius: 7px;
            background: #fff;
            color: #111;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 12px;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            box-shadow: 0 8px 22px rgba(0, 143, 189, .08);
        }

        .cartas-user-menu__trigger span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cartas-user-menu__trigger:hover,
        .cartas-user-menu__trigger:focus {
            border-color: #a800d6;
            color: #8000a5;
            outline: none;
        }

        .cartas-user-menu__dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            width: 156px;
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 7px;
            background: #fff;
            box-shadow: 0 18px 38px rgba(0, 0, 0, .14);
            padding: 6px;
            display: none;
            z-index: 20;
        }

        .cartas-user-menu.is-open .cartas-user-menu__dropdown {
            display: block;
        }

        .cartas-user-menu__item {
            width: 100%;
            min-height: 34px;
            border: 0;
            border-radius: 5px;
            background: transparent;
            color: #333;
            text-align: left;
            padding: 0 10px;
            font-size: 13px;
            font-weight: 700;
        }

        .cartas-user-menu__item:hover,
        .cartas-user-menu__item:focus {
            background: #f4f0ec;
            color: #a800d6;
            outline: none;
        }

        .cartas-dashboard__main {
            min-height: calc(100vh - 86px);
            background: #fff;
        }

        @media (max-width: 640px) {
            .cartas-dashboard__header {
                height: auto;
                min-height: 86px;
                padding: 16px 18px;
            }

            .cartas-dashboard__header-inner {
                grid-template-columns: 1fr;
                justify-items: center;
                gap: 12px;
            }

            .cartas-dashboard__header-spacer {
                display: none;
            }

            .cartas-user-menu {
                justify-self: center;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const userMenu = document.getElementById('cartasUserMenu');
            const userMenuTrigger = userMenu?.querySelector('.cartas-user-menu__trigger');
                event.stopPropagation();
                const isOpen = userMenu?.classList.toggle('is-open') ?? false;
                userMenuTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            document.addEventListener('click', (event) => {
                if (! userMenu || userMenu.contains(event.target)) {
                    return;
                }

                userMenu.classList.remove('is-open');
                userMenuTrigger?.setAttribute('aria-expanded', 'false');
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                userMenu?.classList.remove('is-open');
                userMenuTrigger?.setAttribute('aria-expanded', 'false');
            });
        });
    </script>
@endpush
