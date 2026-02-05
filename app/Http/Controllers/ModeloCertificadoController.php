<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModeloCertificadoRequest;
use App\Models\Eixo;
use App\Models\ModeloCertificado;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Closure;
use Illuminate\Foundation\Configuration\Middleware;

class ModeloCertificadoController extends Controller
{
    public function __construct()
    {
        //$this->middleware(['auth', 'role:administrador|gestor']);
    }

    public function index(): View
    {
        $modelos = ModeloCertificado::with('eixo')
            ->orderBy('nome')
            ->paginate(12);

        return view('certificados.modelos.index', compact('modelos'));
    }

    public function create(): View
    {
        $eixos = Eixo::orderBy('nome')->pluck('nome', 'id');
        return view('certificados.modelos.create', compact('eixos'));
    }

    public function store(ModeloCertificadoRequest $request): RedirectResponse
    {
        $data = $this->normalizeLayout($request->validated());
        if ($request->hasFile('imagem_frente')) {
            $data['imagem_frente'] = $request->file('imagem_frente')->store('certificados', 'public');
        }
        if ($request->hasFile('imagem_verso')) {
            $data['imagem_verso'] = $request->file('imagem_verso')->store('certificados', 'public');
        }

        ModeloCertificado::create($data);
        return redirect()->route('certificados.modelos.index')
            ->with('success', 'Modelo criado com sucesso.');
    }

    public function edit(ModeloCertificado $modelo): View
    {
        $eixos = Eixo::orderBy('nome')->pluck('nome', 'id');
        return view('certificados.modelos.edit', compact('modelo', 'eixos'));
    }

    public function update(ModeloCertificadoRequest $request, ModeloCertificado $modelo): RedirectResponse
    {
        $data = $this->normalizeLayout($request->validated());

        if ($request->hasFile('imagem_frente')) {
            $data['imagem_frente'] = $request->file('imagem_frente')->store('certificados', 'public');
        } else {
            $data['imagem_frente'] = $modelo->imagem_frente;
        }

        if ($request->hasFile('imagem_verso')) {
            $data['imagem_verso'] = $request->file('imagem_verso')->store('certificados', 'public');
        } else {
            $data['imagem_verso'] = $modelo->imagem_verso;
        }

        $modelo->update($data);
        return redirect()->route('certificados.modelos.index')
            ->with('success', 'Modelo atualizado com sucesso.');
    }

    public function destroy(ModeloCertificado $modelo): RedirectResponse
    {
        $modelo->delete();
        return redirect()->route('certificados.modelos.index')
            ->with('success', 'Modelo removido com sucesso.');
    }

    private function normalizeLayout(array $data): array
    {
        foreach (['layout_frente','layout_verso'] as $key) {
            if (!empty($data[$key]['styles']) && is_string($data[$key]['styles'])) {
                $decoded = json_decode($data[$key]['styles'], true);
                $data[$key]['styles'] = is_array($decoded) ? $decoded : null;
            }
        }
        return $data;
    }
}
