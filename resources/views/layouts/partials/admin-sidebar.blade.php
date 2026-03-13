<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-sidebar__brand">
    <a href="{{ url('/') }}" class="d-flex align-items-center gap-3 text-decoration-none text-white">
      <img src="{{ asset('images/engaja-bg-white.png') }}" alt="Logo Engaja" class="admin-sidebar__logo admin-sidebar__logo-main">
      <img src="{{ asset('images/engaja-favicon.png') }}" alt="Logo mini" class="admin-sidebar__logo admin-sidebar__logo-mini">
    </a>
    <button type="button" class="btn btn-sm btn-outline-light d-lg-none" id="sidebarClose">Fechar</button>
  </div>

  <div class="admin-sidebar__section">
    <p class="admin-sidebar__label">Navegação</p>
    <a class="admin-nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">
      <span class="admin-nav-icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
          <path d="M7.293 1.5a1 1 0 0 1 1.414 0l5.793 5.793a1 1 0 0 1 .293.707V14a1 1 0 0 1-1 1h-4a.5.5 0 0 1-.5-.5V11a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v3.5a.5.5 0 0 1-.5.5h-4a1 1 0 0 1-1-1V8c0-.266.105-.52.293-.707z"/>
        </svg>
      </span>
      <span class="admin-nav-text">Inicio</span>
    </a>
  </div>

  <div class="admin-sidebar__section">
    <p class="admin-sidebar__label">Principal</p>
    @hasanyrole('administrador|gerente|eq_pedagogica|articulador')
      <a class="admin-nav-link {{ request()->routeIs('eventos.*') ? 'active' : '' }}" href="{{ route('eventos.index') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M2 2h12v2H2zM2 6h9v2H2zM2 10h6v2H2z"/>
          </svg>
        </span>
        <span class="admin-nav-text">Ações pedagógicas</span>
      </a>
    @endhasanyrole
    @hasanyrole('administrador|gerente|eq_pedagogica|articulador')
      <a class="admin-nav-link {{ request()->routeIs('dashboard') || request()->routeIs('dashboards.*') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3 2h3v5H3zM10 2h3v3h-3zM10 7h3v7h-3zM3 9h3v5H3z"/>
          </svg>
        </span>
        <span class="admin-nav-text">Dashboards</span>
      </a>
    @endhasanyrole
  </div>

  @hasanyrole('administrador|gerente|eq_pedagogica|articulador')
    <div class="admin-sidebar__section">
      <p class="admin-sidebar__label">Avaliações</p>
      <a class="admin-nav-link {{ request()->routeIs('dimensaos.*') ? 'active' : '' }}" href="{{ route('dimensaos.index') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M7.293 1.5a1 1 0 0 1 1.414 0l5.793 5.793a1 1 0 0 1 .293.707V14a1 1 0 0 1-1 1h-4a.5.5 0 0 1-.5-.5V11a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v3.5a.5.5 0 0 1-.5.5h-4a1 1 0 0 1-1-1V8c0-.266.105-.52.293-.707z"/>
          </svg>
        </span>
        <span class="admin-nav-text">Dimensões</span>
      </a>
      <a class="admin-nav-link {{ request()->routeIs('indicadors.*') ? 'active' : '' }}" href="{{ route('indicadors.index') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M1 13V3a1 1 0 0 1 1-1h2v12H2a1 1 0 0 1-1-1m5-9a1 1 0 0 1 1-1h2v12H7a1 1 0 0 1-1-1zm5 2a1 1 0 0 1 1-1h2v12h-2a1 1 0 0 1-1-1z"/>
          </svg>
        </span>
        <span class="admin-nav-text">Indicadores</span>
      </a>
      <a class="admin-nav-link {{ request()->routeIs('evidencias.*') ? 'active' : '' }}" href="{{ route('evidencias.index') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.32-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.63.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187z"/>
          </svg>
        </span>
        <span class="admin-nav-text">Evidências</span>
      </a>
      <a class="admin-nav-link {{ request()->routeIs('escalas.*') ? 'active' : '' }}" href="{{ route('escalas.index') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 1a1 1 0 0 1 .894.553L13 10h1a1 1 0 0 1 0 2h-3a1 1 0 0 1-.894-.553L8 4.618 5.894 11.447A1 1 0 0 1 5 12H2a1 1 0 0 1 0-2h1l4.106-8.447A1 1 0 0 1 8 1"/>
          </svg>
        </span>
        <span class="admin-nav-text">Escalas</span>
      </a>
      <a class="admin-nav-link {{ request()->routeIs('templates-avaliacao.*') ? 'active' : '' }}" href="{{ route('templates-avaliacao.index') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M5.5 2A1.5 1.5 0 0 1 7 3.5v9A1.5 1.5 0 0 1 5.5 14h-3A1.5 1.5 0 0 1 1 12.5v-9A1.5 1.5 0 0 1 2.5 2zM15 4.5a1.5 1.5 0 0 0-1.5-1.5H9v10h4.5A1.5 1.5 0 0 0 15 11.5z"/>
          </svg>
        </span>
        <span class="admin-nav-text">Modelos de avaliação</span>
      </a>
      <a class="admin-nav-link {{ request()->routeIs('avaliacoes.*') ? 'active' : '' }}" href="{{ route('avaliacoes.index') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3 3a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V6.414a1 1 0 0 0-.293-.707l-3.414-3.414A1 1 0 0 0 9.586 2H3zm6-1.5v3a.5.5 0 0 0 .5.5h3z"/>
          </svg>
        </span>
        <span class="admin-nav-text">Avaliações</span>
      </a>
      @hasanyrole('administrador|gerente')
        <a class="admin-nav-link {{ request()->routeIs('avaliacao-atividade.*') ? 'active' : '' }}" href="{{ route('avaliacao-atividade.index') }}">
          <span class="admin-nav-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
              <path d="M3 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6.5L8.5 2zM8 3.5V7a1 1 0 0 0 1 1h3.5"/>
            </svg>
          </span>
          <span class="admin-nav-text">Relatórios da ação</span>
        </a>
      @endhasanyrole
    </div>
  @endhasanyrole

  @role('administrador')
    <div class="admin-sidebar__section">
      <p class="admin-sidebar__label">Gerenciamento</p>
      <a class="admin-nav-link {{ request()->routeIs('regioes.*') ? 'active' : '' }}" href="{{ route('regioes.index') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M2 3a1 1 0 0 1 1-1h4v4H2zm0 5h5v6H3a1 1 0 0 1-1-1zm6 6V8h6v5a1 1 0 0 1-1 1zm6-7H8V2h5a1 1 0 0 1 1 1z"/>
          </svg>
        </span>
        <span class="admin-nav-text">Regiões</span>
      </a>
      <a class="admin-nav-link {{ request()->routeIs('estados.*') ? 'active' : '' }}" href="{{ route('estados.index') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h11A1.5 1.5 0 0 1 15 2.5v11a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 1 13.5zm4 2a.5.5 0 0 0-.5.5v6a.5.5 0 0 0 1 0V8h5v3a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5z"/>
          </svg>
        </span>
        <span class="admin-nav-text">Estados</span>
      </a>
      <a class="admin-nav-link {{ request()->routeIs('municipios.*') ? 'active' : '' }}" href="{{ route('municipios.index') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1m0 1a6 6 0 1 1 0 12A6 6 0 0 1 8 2m-2 4a1 1 0 1 0 0 2 1 1 0 0 0 0-2m4 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-4 3a1 1 0 1 0 0 2 1 1 0 0 0 0-2m4 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
          </svg>
        </span>
        <span class="admin-nav-text">Municípios</span>
      </a>
    </div>
  @endrole

  @hasanyrole('administrador|gerente|eq_pedagogica|articulador')
    <div class="admin-sidebar__section">
      <p class="admin-sidebar__label">Pessoas</p>
      <a class="admin-nav-link {{ request()->routeIs('usuarios.index') || request()->routeIs('usuarios.edit') || request()->routeIs('usuarios.update') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 9a3 3 0 1 0-3-3 3 3 0 0 0 3 3m4.5 5a.5.5 0 0 0 .5-.5c0-1.657-2.239-3-5-3s-5 1.343-5 3a.5.5 0 0 0 .5.5z"/>
          </svg>
        </span>
        <span class="admin-nav-text">Gerenciar usuários</span>
      </a>
      <a class="admin-nav-link {{ request()->routeIs('usuarios.verificar.*') ? 'active' : '' }}" href="{{ route('usuarios.verificar.index') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1m0 2a5 5 0 1 1 0 10A5 5 0 0 1 8 3m2.354 3.146a.5.5 0 0 0-.708 0L7.5 8.293 6.354 7.146a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0l2.5-2.5a.5.5 0 0 0 0-.708"/>
          </svg>
        </span>
        <span class="admin-nav-text">Verificar usuário</span>
      </a>
    </div>
  @endhasanyrole

  @hasanyrole('administrador|gerente|eq_pedagogica|articulador|participante')
    <div class="admin-sidebar__section">
      <p class="admin-sidebar__label">Certificados</p>
      <a class="admin-nav-link {{ request()->routeIs('profile.certificados') ? 'active' : '' }}" href="{{ route('profile.certificados') }}">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M2 1.5A1.5 1.5 0 0 1 3.5 0h6A1.5 1.5 0 0 1 11 1.5V4h1.5A1.5 1.5 0 0 1 14 5.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z"/>
          </svg>
        </span>
        <span class="admin-nav-text">Meus certificados</span>
      </a>
      @hasanyrole('administrador|gerente')
        <a class="admin-nav-link {{ request()->routeIs('certificados.modelos.*') ? 'active' : '' }}" href="{{ route('certificados.modelos.index') }}">
          <span class="admin-nav-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
              <path d="M3 1h10a2 2 0 0 1 2 2v11.5a.5.5 0 0 1-.777.416L10 13.101 5.777 14.916A.5.5 0 0 1 5 14.5V3a2 2 0 0 1 2-2z"/>
            </svg>
          </span>
          <span class="admin-nav-text">Modelos de certificados</span>
        </a>
        <a class="admin-nav-link {{ request()->routeIs('certificados.emitidos') ? 'active' : '' }}" href="{{ route('certificados.emitidos') }}">
          <span class="admin-nav-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
              <path d="M2 2a2 2 0 0 0-2 2v8.5a1.5 1.5 0 0 0 3 0V4a2 2 0 0 0-1-1.732A2 2 0 0 0 2 2m11.293 3.707a1 1 0 0 0-1.414-1.414l-6.293 6.293v1.414h1.414z"/>
              <path d="M11.5 14.5a.5.5 0 0 0 .5-.5V8.707l-5.293 5.293a.5.5 0 0 0 .353.853z"/>
            </svg>
          </span>
          <span class="admin-nav-text">Certificados emitidos</span>
        </a>
      @endhasanyrole
    </div>
  @endhasanyrole

  <div class="admin-sidebar__section">
    <p class="admin-sidebar__label">Minha conta</p>
    <a class="admin-nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
      <span class="admin-nav-icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
          <path d="M8 8a3 3 0 1 0-3-3 3 3 0 0 0 3 3m4 5.5a5 5 0 1 0-8 0z"/>
        </svg>
      </span>
      <span class="admin-nav-text">Meu perfil</span>
    </a>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="admin-nav-link btn btn-link text-start w-100 px-0">
        <span class="admin-nav-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M6.146 11.854a.5.5 0 0 0 .708 0L10.207 8.5 6.854 5.146a.5.5 0 1 0-.708.708L8.793 8.5z"/>
            <path d="M3.5 15A1.5 1.5 0 0 1 2 13.5v-11A1.5 1.5 0 0 1 3.5 1h5A1.5 1.5 0 0 1 10 2.5V5h-1V2.5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0-.5.5v11a.5.5 0 0 0 .5.5h5a.5.5 0 0 0 .5-.5V10h1v3.5a1.5 1.5 0 0 1-1.5 1.5z"/>
          </svg>
        </span>
        <span class="admin-nav-text">Sair</span>
      </button>
    </form>
  </div>
</aside>
