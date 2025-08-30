<footer class="bg-light border-top mt-5 pt-5">
  <div class="container">

    <!-- Logo + Links  -->
    <div class="row text-center text-md-start align-items-center justify-content-center g-4">
      <!-- Logo -->
      <div class="col-md-4 d-flex flex-column align-items-center">
        <img src="{{ asset('images/engaja-bg.png') }}" alt="Engaja" style="height:48px">
        <p class="text-muted mt-2 mb-0 text-center">
          Sistema de Gestão de Participação e Engajamento.
        </p>
      </div>

      <div class="col-md-4 d-flex flex-column align-items-center">
        <a href="{{ route('eventos.index') }}" class="mb-2 text-decoration-none text-dark fw-semibold link-hover">Eventos</a>
        <a href="#" class="mb-2 text-decoration-none text-dark fw-semibold link-hover">Pesquisar</a>
        <a href="#" class="mb-2 text-decoration-none text-dark fw-semibold link-hover">Pesquisas e Memórias</a>
      </div>
    </div>

    <hr class="my-4">

    <!-- Realização e Parceria -->
    <div class="row align-items-center text-center g-4">
      <div class="col-md-6">
        <div class="fw-bold mb-2">Realização</div>
        <img src="{{ asset('images/ipf.png') }}" alt="Instituto" class="img-fluid" style="max-height:42px">
      </div>
      <div class="col-md-6">
        <div class="fw-bold mb-2">Parceria</div>
        <img src="{{ asset('images/petrobras.png') }}" alt="Parceiro" class="img-fluid" style="max-height:42px">
      </div>
    </div>

  </div>

  <div class="footer-legal text-center py-3 mt-4">
    <small class="text-muted">
      INSTITUTO DE EDUCAÇÃO E DIREITOS HUMANOS PAULO FREIRE | CNPJ 04.950.603/0001-05
    </small>
  </div>
</footer>
