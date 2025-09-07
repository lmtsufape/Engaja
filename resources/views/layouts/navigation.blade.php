<nav class="navbar navbar-expand-lg navbar-dark bg-primary border-bottom">
  <div class="container">
    {{-- Logo + texto --}}
    <a class="navbar-brand d-flex align-items-center text-engaja fw-bold" href="{{ url('/') }}">
      <img src="{{ asset('images/logo-alfaeja.svg') }}" alt="Logo ALFA-EJA Brasil"
        class="me-2" style="height:40px;">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div id="mainNav" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

      </ul>

      <ul class="navbar-nav ms-auto">
        @guest
        {{-- @if (Route::has('login'))
        <li class="nav-item"><a class="nav-link text-white" href="{{ route('login') }}">Entrar</a></li>
        @endif
        @if (Route::has('register'))
        <li class="nav-item"><a class="nav-link text-white" href="{{ route('register') }}">Cadastrar</a></li>
        @endif --}}
        @else
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white" href="#" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            Olá, {{ Auth::user()->name }}
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Meu perfil</a></li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item">Sair</button>
              </form>
            </li>
          </ul>
        </li>
        @endguest
      </ul>
    </div>
  </div>
</nav>
