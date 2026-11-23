<header style="background:#fff;padding:18px 28px;">
    <div style="width:100%;display:grid;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);align-items:center;gap:20px;">
        <span aria-hidden="true"></span>

        <a href="{{ route('cartas.dashboard') }}" aria-label="Cartas para Esperançar">
            <img src="{{ asset('images/cartas/cartas-logo.png') }}" alt="Cartas para Esperançar" style="width:118px;height:auto;">
        </a>

        <div class="cartas-user-menu" id="cartasUserMenu" style="position:relative;justify-self:end;">
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
