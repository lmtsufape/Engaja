<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'Engaja') }}</title>
  <link rel="icon" type="image/png" href="{{ asset('images/engaja-favicon.png') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">

  {{-- O Vite agora controla todas as cores via SASS --}}
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])

  @stack('styles')
</head>

<body class="d-flex flex-column min-vh-100">
  @includeWhen(View::exists('layouts.navigation'), 'layouts.navigation')

  @isset($header)
  <header class="bg-white border-bottom py-3">
    <div class="container">{{ $header }}</div>
  </header>
  @endisset

  @if (session('success'))
  <div class="alert alert-success text-center">{{ session('success') }}</div>
  @endif

  @if (session('error'))
  <div class="alert alert-danger text-center">{{ session('error') }}</div>
  @endif

  <main class="flex-grow-1 py-4">
    <div class="container">
      @isset($slot) {{ $slot }} @else @yield('content') @endisset
    </div>
  </main>

  @include('layouts.footer')

  <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content shadow-lg border-0">
        <div class="modal-header bg-engaja text-white">
          <h5 class="modal-title" id="confirmModalLabel">Confirmar ação</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <p class="mb-0 js-confirm-message"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-engaja js-confirm-accept">Confirmar</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
