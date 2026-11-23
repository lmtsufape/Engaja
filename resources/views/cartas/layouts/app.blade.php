<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cartas para Esperançar')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/cartas/cartas-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        :root {
            --cartas-blue: #008fbd;
            --cartas-purple: #a800d6;
            --cartas-paper: #f4f0ec;
            --cartas-text: #050505;
        }

        body {
            margin: 0;
            font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            color: var(--cartas-text);
            background: var(--cartas-paper);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .cartas-auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(280px, 1fr) minmax(420px, 1fr);
            background: linear-gradient(90deg, var(--cartas-blue) 0 50%, var(--cartas-paper) 50% 100%);
        }

        .cartas-auth-side {
            background-color: var(--cartas-blue);
            min-height: 100vh;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .cartas-auth-main {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            background: var(--cartas-paper);
        }

        .cartas-auth-content {
            width: min(100%, 508px);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .cartas-logo-link {
            display: block;
            text-align: center;
        }

        .cartas-logo {
            width: 210px;
            max-width: 70%;
            height: auto;
            margin-bottom: 48px;
        }

        .cartas-logo--compact {
            margin-bottom: 32px;
        }

        .cartas-title {
            font-size: 34px;
            line-height: 1.15;
            font-weight: 500;
            margin: 0 0 26px;
            text-align: center;
        }

        .cartas-title--strong {
            font-weight: 800;
        }

        .cartas-copy {
            max-width: 420px;
            font-size: 17px;
            line-height: 1.35;
            text-align: center;
            margin: 0;
        }

        .cartas-form {
            width: 100%;
        }

        .cartas-field {
            width: 100%;
            height: 40px;
            border: 1px solid #111;
            border-radius: 7px;
            background: transparent;
            padding: 0 10px;
            font-size: 16px;
            font-weight: 500;
            outline: none;
        }

        .cartas-field::placeholder {
            color: var(--cartas-text);
            opacity: 1;
        }

        .cartas-field + .cartas-field,
        .cartas-field-wrap + .cartas-field-wrap {
            margin-top: 8px;
        }

        .cartas-field:focus {
            border-color: var(--cartas-purple);
            box-shadow: 0 0 0 3px rgba(168, 0, 214, .14);
        }

        .cartas-field:disabled,
        .cartas-field:read-only {
            background: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
            border-color: #d1d5db;
        }

        .cartas-button {
            width: 100%;
            height: 45px;
            border: 0;
            border-radius: 7px;
            background: var(--cartas-purple);
            color: #fff;
            font-size: 17px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            margin-top: 28px;
        }

        .cartas-button:hover,
        .cartas-button:focus {
            color: #fff;
            background: #9600c6;
        }

        .cartas-links {
            width: 100%;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 28px;
            font-weight: 700;
        }

        .cartas-link {
            color: #5f5f5f;
            text-decoration: none;
        }

        .cartas-link:hover,
        .cartas-link:focus {
            color: var(--cartas-purple);
        }

        .cartas-check {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .cartas-check input {
            width: 14px;
            height: 14px;
            margin: 0;
        }

        .cartas-terms-box {
            width: 100%;
            height: 354px;
            overflow-y: auto;
            border-radius: 18px;
            background: #e4ded9;
            padding: 20px;
            font-size: 15px;
            line-height: 1.2;
            color: #181818;
        }

        .cartas-terms-box p {
            margin: 0 0 12px;
        }

        .cartas-terms-box strong {
            display: block;
            margin-bottom: 8px;
        }

        .cartas-alert {
            width: 100%;
            border-radius: 7px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size: 14px;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .14);
        }

        .cartas-alert--error {
            background: transparent;
            border: 0;
            padding: 0;
            color: #d03030;
            font-weight: 600;
            text-align: center;
        }

        .cartas-app-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--cartas-paper);
        }

        .cartas-app-main {
            flex: 1;
            width: min(100%, 1040px);
            margin: 0 auto;
            padding: 48px 24px;
        }

        @media (max-width: 860px) {
            .cartas-auth-shell {
                grid-template-columns: 1fr;
                background: var(--cartas-paper);
            }

            .cartas-auth-side {
                display: none;
            }

            .cartas-auth-main {
                padding: 36px 22px;
            }

            .cartas-logo {
                margin-bottom: 64px;
            }

            .cartas-logo--compact {
                margin-bottom: 42px;
            }

            .cartas-title {
                font-size: 30px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    @yield('body')
    @include('cartas.layouts.footer')
    @include('cartas.shared._welcome_modal')
    @stack('scripts')
</body>
</html>
