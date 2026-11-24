<?php

namespace App\Http\Controllers;

use App\Imports\AutorizacaoImagemAcaoImport;
use App\Models\AutorizacaoImagem;
use App\Models\Evento;
use App\Services\AutorizacaoImagemImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AutorizacaoImagemAcaoImportController extends Controller
{
    public function import(Evento $evento): View
    {
        $this->authorizeAction($evento);

        return view('autorizacoes-imagem.import', compact('evento'));
    }

    public function template(Evento $evento): StreamedResponse
    {
        $this->authorizeAction($evento);

        return response()->streamDownload(function () {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Autorizacoes');
            $sheet->fromArray(['nome', 'cpf'], null, 'A1');
            $sheet->freezePane('A2');
            $sheet->getStyle('A1:B1')->getFont()->setBold(true);
            $sheet->getStyle('A2:B5001')->getNumberFormat()->setFormatCode('@');
            $sheet->getColumnDimension('A')->setWidth(40);
            $sheet->getColumnDimension('B')->setWidth(20);
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'modelo_autorizacao_imagem.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function upload(
        Request $request,
        Evento $evento,
        AutorizacaoImagemAcaoImport $import,
        AutorizacaoImagemImportService $service,
    ): RedirectResponse {
        $this->authorizeAction($evento);

        $request->validate([
            'your_file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        try {
            $rows = $import->read($request->file('your_file')->getRealPath());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'your_file' => 'Não foi possível ler a planilha. Confira o formato e tente novamente.',
            ]);
        }

        $key = $this->sessionPrefix($evento).Str::uuid();
        session([$key => [
            'evento_id' => $evento->id,
            'user_id' => $request->user()->id,
            'rows' => $service->normalize($rows),
        ]]);

        return redirect()->route('autorizacoes-imagem.import.preview', [
            'evento' => $evento,
            'session_key' => $key,
        ]);
    }

    public function preview(
        Request $request,
        Evento $evento,
        AutorizacaoImagemImportService $service,
    ): View|RedirectResponse {
        $this->authorizeAction($evento);

        $loaded = $this->loadPayload($request, $evento);
        if ($loaded instanceof RedirectResponse) {
            return $loaded;
        }
        [$key, $payload] = $loaded;

        $analysis = $service->analyze($evento, $payload['rows']);
        $perPage = max(1, min(100, $request->integer('per_page', 50)));
        $total = count($analysis['rows']);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($lastPage, $request->integer('page', 1)));

        $slice = array_slice($analysis['rows'], ($page - 1) * $perPage, $perPage, true);
        $rows = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => route('autorizacoes-imagem.import.preview', $evento),
                'query' => ['session_key' => $key, 'per_page' => $perPage],
            ]
        );

        return view('autorizacoes-imagem.preview', [
            'evento' => $evento,
            'rows' => $rows,
            'analysis' => $analysis,
            'sessionKey' => $key,
        ]);
    }

    public function savePage(
        Request $request,
        Evento $evento,
        AutorizacaoImagemImportService $service,
    ): RedirectResponse {
        $this->authorizeAction($evento);

        $loaded = $this->loadPayload($request, $evento);
        if ($loaded instanceof RedirectResponse) {
            return $loaded;
        }
        [$key, $payload] = $loaded;

        $data = $request->validate([
            'session_key' => 'required|string|max:150',
            'rows' => 'required|array',
            'rows.*' => 'array:nome,cpf',
            'rows.*.nome' => 'nullable|string|max:255',
            'rows.*.cpf' => 'nullable|string|max:255',
            'remove' => 'sometimes|array',
        ]);

        $remove = $data['remove'] ?? [];
        foreach ($data['rows'] as $index => $row) {
            if (! ctype_digit((string) $index) || ! array_key_exists((int) $index, $payload['rows'])) {
                throw ValidationException::withMessages(['rows' => 'A linha editada não pertence a esta importação.']);
            }
            $payload['rows'][(int) $index] = array_merge($payload['rows'][(int) $index], $row);
        }

        $removedCount = 0;
        foreach (array_keys($remove) as $index) {
            if (ctype_digit((string) $index) && array_key_exists((int) $index, $payload['rows'])) {
                unset($payload['rows'][(int) $index]);
                $removedCount++;
            }
        }

        $remaining = $service->normalize($payload['rows']);
        if ($remaining === []) {
            session()->forget($key);

            return redirect()->route('autorizacoes-imagem.import', $evento)
                ->withErrors(['your_file' => 'Todas as linhas da prévia foram removidas. Envie uma nova planilha para continuar.']);
        }

        $payload['rows'] = $remaining;
        session([$key => $payload]);

        $perPage = max(1, min(100, $request->integer('per_page', 50)));
        $lastPage = max(1, (int) ceil(count($remaining) / $perPage));
        $page = max(1, min($lastPage, $request->integer('page', 1)));

        $message = $removedCount > 0
            ? "Alterações salvas ({$removedCount} linha(s) removida(s)). Confira as pendências antes de confirmar."
            : 'Alterações desta página salvas. Confira as pendências antes de confirmar.';

        return redirect()->route('autorizacoes-imagem.import.preview', [
            'evento' => $evento,
            'session_key' => $key,
            'page' => $page,
            'per_page' => $perPage,
        ])->withFragment('pendencias-autorizacao')->with('success', $message);
    }

    public function removePending(
        Request $request,
        Evento $evento,
        AutorizacaoImagemImportService $service,
    ): RedirectResponse {
        $this->authorizeAction($evento);

        $loaded = $this->loadPayload($request, $evento);
        if ($loaded instanceof RedirectResponse) {
            return $loaded;
        }
        [$key, $payload] = $loaded;

        $analysis = $service->analyze($evento, $payload['rows']);
        $errorIndexes = array_keys($analysis['errors']);

        if ($errorIndexes === []) {
            return redirect()->route('autorizacoes-imagem.import.preview', [
                'evento' => $evento,
                'session_key' => $key,
            ])->with('success', 'Não há linhas com pendência para remover.');
        }

        foreach ($errorIndexes as $index) {
            unset($payload['rows'][$index]);
        }

        $remaining = $service->normalize($payload['rows']);
        if ($remaining === []) {
            session()->forget($key);

            return redirect()->route('autorizacoes-imagem.import', $evento)
                ->withErrors(['your_file' => 'Todas as linhas possuíam pendências e foram removidas. Corrija a planilha e envie novamente.']);
        }

        $payload['rows'] = $remaining;
        session([$key => $payload]);

        return redirect()->route('autorizacoes-imagem.import.preview', [
            'evento' => $evento,
            'session_key' => $key,
            'page' => 1,
            'per_page' => max(1, min(100, $request->integer('per_page', 50))),
        ])->withFragment('pendencias-autorizacao')->with('success', count($errorIndexes).' linha(s) com pendência removida(s) da prévia.');
    }

    public function confirm(
        Request $request,
        Evento $evento,
        AutorizacaoImagemImportService $service,
    ): RedirectResponse {
        $this->authorizeAction($evento);

        $loaded = $this->loadPayload($request, $evento);
        if ($loaded instanceof RedirectResponse) {
            return $loaded;
        }
        [$key, $payload] = $loaded;

        $result = $service->apply($evento, $payload['rows'], $request->user());
        session()->forget($key);

        $partes = [
            $result['autorizadas'].' nova(s) autorização(ões) registrada(s)',
        ];
        if ($result['alteradas_de_recusa'] > 0) {
            $partes[] = $result['alteradas_de_recusa'].' alterada(s) de Não autorizada para Autorizada';
        }
        if ($result['ja_autorizadas'] > 0) {
            $partes[] = $result['ja_autorizadas'].' já estava(m) autorizada(s)';
        }

        return redirect()->route('autorizacoes-imagem.index', ['evento_id' => $evento->id])
            ->with('success', 'Importação de autorizações concluída: '.implode('; ', $partes).'.');
    }

    private function authorizeAction(Evento $evento): void
    {
        Gate::authorize('create', AutorizacaoImagem::class);
        abort_unless($evento->exists && ! $evento->trashed(), 404);
    }

    private function sessionPrefix(Evento $evento): string
    {
        return "autorizacao_imagem_import_evento_{$evento->id}_";
    }

    private function loadPayload(Request $request, Evento $evento): array|RedirectResponse
    {
        $key = (string) ($request->input('session_key') ?? $request->query('session_key') ?? '');
        $payload = ($key !== '' && strlen($key) <= 150 && Str::startsWith($key, $this->sessionPrefix($evento)))
            ? session($key)
            : null;

        if (! is_array($payload)
            || ($payload['evento_id'] ?? null) !== $evento->id
            || ($payload['user_id'] ?? null) !== $request->user()->id
            || empty($payload['rows'])
            || ! is_array($payload['rows'])) {
            return redirect()->route('autorizacoes-imagem.import', $evento)
                ->withErrors(['your_file' => 'Sessão de importação inválida ou expirada. Envie a planilha novamente.']);
        }

        return [$key, $payload];
    }
}
