@extends('cartas.layouts.app')

@section('title', 'Editar usuário - Cartas para Esperançar')

@section('body')
    @include('cartas.shared._styles')

    <main class="cpe-page cpe-user-edit-page">
        @include('cartas.shared._logo')

        <section class="cpe-user-edit-card">
            <h1 class="cpe-title">Editar usuário</h1>
            <p>Atualize os dados e o perfil de acesso no Cartas.</p>

            @if ($errors->any())
                <div class="cpe-alert cpe-alert--error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('cartas.usuarios.update', $managedUser) }}" class="cpe-user-form">
                @csrf
                @method('PUT')

                <label>
                    <span>Nome</span>
                    <input type="text" name="name" class="cpe-field" value="{{ old('name', $managedUser->name) }}" required>
                </label>

                <label>
                    <span>E-mail</span>
                    <input type="email" name="email" class="cpe-field" value="{{ old('email', $managedUser->email) }}" required>
                </label>

                <label>
                    <span>Perfil de acesso</span>
                    @php($currentRole = $managedUser->roles->pluck('name')->first(fn ($name) => array_key_exists($name, $roles)))
                    <select name="role" class="cpe-select" required>
                        @foreach($roles as $role => $label)
                            <option value="{{ $role }}" @selected(old('role', $currentRole) === $role)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>Limite de cartas</span>
                    <select name="cartas_limite_respostas" class="cpe-select" required>
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" @selected(old('cartas_limite_respostas', $managedUser->cartas_limite_respostas) == $i)>{{ $i }}</option>
                        @endfor
                    </select>
                </label>

                <label>
                    <span>Tipo de vínculo</span>
                    <select name="cartas_tipo_vinculo" class="cpe-select">
                        <option value="">Não informado</option>
                        @foreach (\App\Models\User::VINCULOS_CARTAS as $value => $label)
                            <option value="{{ $value }}" @selected(old('cartas_tipo_vinculo', $managedUser->cartas_tipo_vinculo) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="cpe-user-form__actions">
                    <a href="{{ route('cartas.usuarios.index') }}" class="cpe-button cpe-button--ghost">Voltar</a>
                    <button type="submit" class="cpe-button">Salvar</button>
                </div>
            </form>
        </section>

        @include('cartas.shared._user-menu')
    </main>

    @include('cartas.shared._scripts')

    <style>
        .cpe-user-edit-page {
            padding: 0 28px 72px;
        }

        .cpe-user-edit-card {
            width: min(100%, 520px);
            margin: 120px auto 0;
            display: grid;
            gap: 16px;
        }

        .cpe-user-edit-card p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .cpe-user-form {
            display: grid;
            gap: 14px;
        }

        .cpe-user-form label {
            display: grid;
            gap: 7px;
            color: #555;
            font-size: 13px;
            font-weight: 700;
        }

        .cpe-user-form__actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 8px;
        }

        @media (max-width: 640px) {
            .cpe-user-edit-card {
                margin-top: 64px;
            }

            .cpe-user-form__actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
