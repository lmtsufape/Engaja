@extends('layouts.app')

@section('content')

    @php
        $munLabel = function ($m) {
            $uf = $m->estado->sigla ?? null;
            return $uf ? "{$m->nome} — {$uf}" : $m->nome;
        };
    @endphp
    <div class="container py-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card shadow-sm">
                    <div class="card-header text-center bg-primary text-white fw-bold">
                        {{ __('Criar conta no Engaja') }}
                    </div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('evento.store_cadastro_inscricao') }}">
                            @csrf

                            <input type="hidden" name="evento_id" value="{{ $evento->id }}">
                            <input type="hidden" name="atividade_id" value="{{ $atividade->id }}">

                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-white">
                                    <strong>Dados do usuário</strong>
                                </div>

                                <div class="card-body">
                                    <div class="row g-3">

                                        {{-- Name --}}
                                        <div class="md-6">
                                            <label for="name" class="form-label">{{ __('Nome') }}</label>
                                            <input id="name" type="text"
                                                class="form-control @error('name') is-invalid @enderror" name="name"
                                                value="{{ old('name') }}" required autofocus>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Email --}}
                                        <div class="mb-3">
                                            <label for="email" class="form-label">{{ __('E-mail') }}</label>
                                            <input id="email" type="email"
                                                class="form-control @error('email') is-invalid @enderror" name="email"
                                                value="{{ old('email') }}" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        {{-- <div class="col-md-6">
                                            <label for="password" class="form-label">{{ __('Senha') }}</label>
                                            <input id="password" type="password"
                                                class="form-control @error('password') is-invalid @enderror" name="password"
                                                required autocomplete="new-password">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                {{ __('Min. 8 caracteres. Use letras, números e/ou símbolos.') }}
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="password_confirmation"
                                                class="form-label">{{ __('Confirmar senha') }}</label>
                                            <input id="password_confirmation" type="password" class="form-control"
                                                name="password_confirmation" required autocomplete="new-password">
                                        </div> --}}
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-white">
                                    <strong>Dados do participante</strong> {{-- <span class="text-muted">(opcionais)</span> --}}
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        {{-- CPF --}}
                                        <div class="col-md-6">
                                            <label for="cpf" class="form-label">CPF</label>
                                            <input id="cpf" type="text" name="cpf" inputmode="numeric" autocomplete="off"
                                                maxlength="14" {{-- 000.000.000-00 --}}
                                                value="{{ old('cpf', $participante->cpf ?? '') }}"
                                                class="form-control @error('cpf') is-invalid @enderror"
                                                placeholder="000.000.000-00">
                                            @error('cpf') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        {{-- Telefone --}}
                                        <div class="col-md-6">
                                            <label for="telefone" class="form-label">Telefone</label>
                                            <input id="telefone" type="text" name="telefone" inputmode="numeric"
                                                autocomplete="tel" maxlength="15" {{-- (99) 99999-9999 --}}
                                                value="{{ old('telefone', $participante->telefone ?? '') }}"
                                                class="form-control @error('telefone') is-invalid @enderror"
                                                placeholder="(99) 99999-9999">
                                            @error('telefone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="tipo_organizacao" class="form-label">Tipo de Instituição</label>
                                            <select id="tipo_organizacao" name="tipo_organizacao"
                                                class="form-select @error('tipo_organizacao') is-invalid @enderror">
                                                <option value="">Selecione...</option>
                                                @foreach(config('engaja.organizacoes', []) as $org)
                                                <option value="{{ $org }}" @selected(old('tipo_organizacao', $participante->tipo_organizacao ?? '') === $org)>{{ $org }}</option>
                                                @endforeach
                                            </select>
                                            @error('tipo_organizacao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="escola_unidade" class="form-label">Nome da instituição</label>
                                            <input id="escola_unidade" type="text" name="escola_unidade"
                                                value="{{ old('escola_unidade', $participante->escola_unidade ?? '') }}"
                                                class="form-control @error('escola_unidade') is-invalid @enderror">
                                            @error('escola_unidade') <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tag" class="form-label">Vínculo no projeto</label>
                                            <select id="tag" name="tag"
                                                class="form-select @error('tag') is-invalid @enderror">
                                                <option value="">Selecione...</option>
                                                @foreach(($participanteTags ?? config('engaja.participante_tags', \App\Models\Participante::TAGS)) as $tagOption)
                                                <option value="{{ $tagOption }}" @selected(old('tag', $participante->tag ?? "") === $tagOption)>{{ $tagOption }}</option>
                                                @endforeach
                                            </select>
                                            @error('tag') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="municipio_id" class="form-label">Município</label>
                                            <select id="municipio_id" name="municipio_id"
                                                class="form-select @error('municipio_id') is-invalid @enderror">
                                                <option value="">— Nenhum —</option>
                                                @foreach($municipios as $m)
                                                    <option value="{{ $m->id }}">
                                                        {{ $munLabel($m) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('municipio_id') <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="d-flex justify-content-between align-items-center">
                                <a class="btn btn-link p-0" href="{{ route('login') }}">
                                    {{ __('Já tem conta? Entrar') }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Cadastrar') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- <div class="card-footer text-center">
                        <small class="text-danger opacity-75">
                            {{ __('Ao criar sua conta, você será inscrito automaticamente no evento e sua presença será confirmada.') }}
                        </small>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>

    <script>
        // aplica máscara enquanto digita (sem libs)
        const onlyDigits = s => (s || '').replace(/\D+/g, '');

        function maskCPF(v) {
            const d = onlyDigits(v).slice(0, 11);
            const p1 = d.slice(0, 3);
            const p2 = d.slice(3, 6);
            const p3 = d.slice(6, 9);
            const p4 = d.slice(9, 11);
            let out = p1;
            if (p2) out += '.' + p2;
            if (p3) out += '.' + p3;
            if (p4) out += '-' + p4;
            return out;
        }

        function maskPhone(v) {
            const d = onlyDigits(v).slice(0, 11);
            const is11 = d.length > 10; // celular com 9 digitos
            const dd = d.slice(0, 2);
            const p1 = is11 ? d.slice(2, 7) : d.slice(2, 6);
            const p2 = is11 ? d.slice(7, 11) : d.slice(6, 10);
            let out = '';
            if (dd) out = `(${dd}`;
            if (dd && (p1 || p2)) out += ') ';
            if (p1) out += p1;
            if (p2) out += '-' + p2;
            return out;
        }

        const cpfEl = document.getElementById('cpf');
        const telEl = document.getElementById('telefone');

        if (cpfEl) {
            cpfEl.addEventListener('input', e => {
                const start = e.target.selectionStart;
                e.target.value = maskCPF(e.target.value);
                // caret: joga pro final (simples e suficiente)
                e.target.setSelectionRange(e.target.value.length, e.target.value.length);
            });
        }
        if (telEl) {
            telEl.addEventListener('input', e => {
                e.target.value = maskPhone(e.target.value);
                e.target.setSelectionRange(e.target.value.length, e.target.value.length);
            });
        }
    </script>
@endsection
