@extends('cartas.layouts.app')

@section('body')
    <main class="cartas-auth-shell">
        <aside class="cartas-auth-side" aria-hidden="true" style="@yield('auth-bg-style')">
            @yield('auth-side-content')
        </aside>
        <section class="cartas-auth-main">
            <div class="cartas-auth-content">
                <a href="{{ Auth::check() ? route('cartas.dashboard') : route('cartas.apresentacao') }}" aria-label="Voltar para o início do Cartas" class="cartas-logo-link">
                    <img
                        src="{{ asset('images/cartas/cartas-logo.png') }}"
                        alt="Cartas para Esperançar"
                        class="cartas-logo @yield('logoClass')"
                    >
                </a>
                @yield('auth-content')
            </div>
        </section>
    </main>
@endsection
