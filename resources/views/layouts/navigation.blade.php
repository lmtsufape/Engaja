<nav class="navbar navbar-expand-lg navbar-dark bg-primary border-bottom sticky-top shadow-sm">
  <div class="container">
    {{-- Logo + texto --}}
    <a class="navbar-brand d-flex align-items-center text-engaja fw-bold" href="{{ url('/') }}">
      <img src="{{ asset('images/engaja-bg-white.png') }}" alt="Logo ALFA-EJA Brasil"
        class="me-2" style="height:40px;">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div id="mainNav" class="collapse navbar-collapse">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
        @auth
        @hasanyrole('administrador|gestor')
        <li class="nav-item">
          <a class="nav-link text-white" href="{{ route('eventos.index') }}">
            Ações Pedagógicas
          </a>
        </li>
        @endhasanyrole
        @role('administrador')
        <li class="nav-item">
          <a class="nav-link text-white" href="{{ route('dashboard') }}">
            Dashboards
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white nav-dropdown-fallback" href="javascript:void(0)" role="button" data-bs-toggle="dropdown"
            aria-expanded="false">
            Avaliações
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('dimensaos.index') }}">Dimensões</a></li>
            <li><a class="dropdown-item" href="{{ route('indicadors.index') }}">Indicadores</a></li>
            <li><a class="dropdown-item" href="{{ route('evidencias.index') }}">Evidências</a></li>
            <li><a class="dropdown-item" href="{{ route('escalas.index') }}">Escalas</a></li>
            <li><a class="dropdown-item" href="{{ route('templates-avaliacao.index') }}">Modelos de avaliação</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('avaliacoes.index') }}">Avaliações</a></li>
          </ul>
        </li>
        @endrole
        @hasanyrole('administrador|gestor')
        <li class="nav-item">
          <a class="nav-link text-white ms-lg-2" href="{{ route('usuarios.index') }}">
            Gerenciar Usuários
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white ms-lg-2" href="{{ route('certificados.modelos.index') }}">
            Certificados
          </a>
        </li>
        @endhasanyrole
        @role('participante')
        <li class="nav-item">
          <a class="nav-link text-white ms-lg-2" href="{{ route('profile.certificados') }}">
            Meus certificados
          </a>
        </li>
        @endrole
        @endauth
      </ul>

      <ul class="navbar-nav ms-auto">
        @guest
        @if (Route::has('login'))
        <li class="nav-item"><a class="nav-link text-white" href="{{ route('login') }}">Entrar</a></li>
        @endif
        @if (Route::has('register'))
        <li class="nav-item"><a class="nav-link text-white" href="{{ route('register') }}">Cadastrar</a></li>
        @endif
        @else
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white nav-dropdown-fallback" href="javascript:void(0)" role="button"
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
<style>
.navbar-nav.mx-auto {
  position: static;
  transform: none;
}

@media (min-width: 992px) {
  .navbar-nav.mx-auto {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
  }
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const toggles = Array.from(document.querySelectorAll('.nav-dropdown-fallback'));

  if (window.bootstrap?.Dropdown) {
    toggles.forEach(t => new window.bootstrap.Dropdown(t));
    return;
  }

  // Fallback caso o JS do Bootstrap não esteja carregado.
  toggles.forEach(toggle => {
    toggle.addEventListener('click', (e) => {
      e.preventDefault();
      const menu = toggle.nextElementSibling;
      if (!menu) return;
      const isOpen = menu.classList.contains('show');
      document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
      if (!isOpen) menu.classList.add('show');
    });
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.nav-item.dropdown')) {
      document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
    }
  });
});
</script>
@endpush
