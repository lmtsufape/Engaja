<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Engaja') }}</title>

    {{-- Fonte Montserrat --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">

    {{-- Vite: Bootstrap SCSS + JS --}}
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
      :root { --engaja-purple: #421944; }
      body { font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
      .navbar-brand { font-weight: 700; letter-spacing: .2px; }
    </style>
    <style>
      .form-control {
          border-color: #b1b6bbff !important; /* cinza escuro padrão Bootstrap */
      }

      .form-control:focus {
          border-color: #421944 !important; /* roxo Engaja no foco */
          box-shadow: 0 0 0 0.2rem rgba(66, 25, 68, 0.25); /* glow roxo no foco */
      }

      .form-select {
          border-color: #b1b6bbff !important;
      }

      .form-select:focus {
          border-color: #421944 !important;
          box-shadow: 0 0 0 0.2rem rgba(66, 25, 68, 0.25);
      }
    </style>
  </head>
  <body>
    {{-- Navbar Bootstrap (substitui o include antigo do Tailwind) --}}
    @includeWhen(View::exists('layouts.navigation'), 'layouts.navigation')

    {{-- Cabeçalho opcional --}}
    @isset($header)
      <header class="bg-white border-bottom py-3">
        <div class="container">
          {{ $header }}
        </div>
      </header>
    @endisset

    {{-- Conteúdo --}}
    <main class="py-4">
      <div class="container">
        {{-- Se você usa $slot (Breeze components) --}}
        @isset($slot)
          {{ $slot }}
        @else
          @yield('content')
        @endisset
      </div>
    </main>
    {{-- Footer --}}
    @include('layouts.footer')
  </body>
</html>
