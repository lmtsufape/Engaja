<footer class="bg-primary border-top mt-5 pt-5">
  <div class="container">

    <!-- Logo + Links  -->
    <div class="row text-center text-md-start align-items-center justify-content-center g-4">
      <!-- Logo -->
      <div class="col-md-6 d-flex flex-column align-items-center">
        <img src="{{ asset('images/logo-alfaeja.svg') }}" alt="Logo ALFA-EJA Brasil" style="height:48px">
        <p class="text-white-50 mt-2 mb-0 text-center">
          Sistema de Gestão de Participação e Engajamento.
        </p>
      </div>
      @can('evento.criar')
        <div class="col-md-6 d-flex flex-column align-items-center">
          <a href="{{ route('eventos.index') }}" class="mb-2 text-decoration-none text-white fw-semibold link-hover">Ações pedagógicas</a>
        </div>
      @endcan
    </div>

    <hr class="my-4 border-light">

    <!-- Realização e Parceria -->
    <div class="row align-items-center text-center g-4">
      <div class="col-md-6">
        <div class="fw-bold mb-2 text-white">Realização</div>
        <img src="{{ asset('images/ipf-white.png') }}" alt="Instituto" class="img-fluid" style="max-height:42px">
      </div>
      <div class="col-md-6">
        <div class="fw-bold mb-2 text-white">Parceria</div>
        <img src="{{ asset('images/petrobras-white.png') }}" alt="Parceiro" class="img-fluid" style="max-height:42px">
      </div>
    </div>

    <hr class="my-4 border-light">
  </div>

  <div class="footer-legal text-center py-3 mt-4">
    <small class="text-white-50">
      INSTITUTO DE EDUCAÇÃO E DIREITOS HUMANOS PAULO FREIRE | CNPJ 04.950.603/0001-05
    </small>
  </div>
</footer>
