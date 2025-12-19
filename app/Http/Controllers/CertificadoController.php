<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Evento;
use App\Models\ModeloCertificado;
use App\Models\Presenca;
use App\Models\Participante;
use App\Mail\CertificadoEmitidoMail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class CertificadoController extends Controller
{
    public function emitir(Request $request)
    {
        $data = $request->validate([
            'modelo_id' => ['required', 'exists:modelo_certificados,id'],
            'eventos'   => ['required'],
        ]);

        $eventosIds = $data['eventos'];
        if (is_string($eventosIds)) {
            $eventosIds = array_filter(explode(',', $eventosIds));
        }
        if (is_array($eventosIds)) {
            $eventosIds = array_map('intval', $eventosIds);
        } else {
            $eventosIds = [];
        }
        $eventosIds = array_unique(array_filter($eventosIds));
        if (empty($eventosIds)) {
            return back()->with('error', 'Selecione ao menos uma ação pedagógica.');
        }

        $modelo = ModeloCertificado::findOrFail($data['modelo_id']);

        $eventos = Evento::with(['presencas.inscricao.participante.user', 'presencas.atividade'])
            ->whereIn('id', $eventosIds)
            ->get();

        $created = 0;
        $paraNotificar = [];
        foreach ($eventos as $evento) {
            // Somat?rio por participante para este evento, apenas presen?as confirmadas ainda n?o certificadas
            $presencasEvento = $evento->presencas
                ->filter(function ($presenca) {
                    return ($presenca->status ?? null) === 'presente'
                        && ! $presenca->certificado_emitido
                        && $presenca->inscricao?->participante?->id;
                });

            $presencasPorParticipante = $presencasEvento
                ->groupBy(fn ($p) => $p->inscricao->participante->id);

            foreach ($presencasPorParticipante as $participanteId => $presencas) {
                $participante = $presencas->first()->inscricao?->participante;
                if (! $participante || ! $participante->user) {
                    continue;
                }

                $cargaTotal = $presencas->sum(function ($p) {
                    return (float) ($p->atividade?->carga_horaria ?? 0);
                });

                $map = [
                    '%participante%'   => $participante->user->name,
                    '%acao%'           => $evento->nome,
                    '%carga_horaria%'  => $cargaTotal,
                ];

                $textoFrente = $this->renderPlaceholders($modelo->texto_frente ?? '', $map);
                $textoVerso  = $this->renderPlaceholders($modelo->texto_verso ?? '', $map);

                $cert = Certificado::create([
                    'modelo_certificado_id' => $modelo->id,
                    'participante_id'       => $participante->id,
                    'evento_nome'           => $evento->nome,
                    'codigo_validacao'      => Str::uuid()->toString(),
                    'ano'                   => (int) ($evento->data_inicio ? date('Y', strtotime($evento->data_inicio)) : date('Y')),
                    'texto_frente'          => $textoFrente,
                    'texto_verso'           => $textoVerso,
                    'carga_horaria'         => $cargaTotal,
                ]);
                if (!empty($participante->user?->email)) {
                    $paraNotificar[] = [$participante->user->email, $participante->user->name, $evento->nome, $cert->id];
                }

                // Marca todas as presen?as deste participante no evento como certificadas
                foreach ($presencas as $presenca) {
                    $presenca->certificado_emitido = true;
                    $presenca->save();
                }

                $created++;
            }
        }

        $this->notificarLote($paraNotificar);

        return redirect()
            ->back()
            ->with('success', "{$created} certificado(s) emitidos com sucesso.");
    }

    private function notificarLote(array $paraNotificar): void
    {
        // Limitamos a ~2 e-mails/segundo (100 e-mails em ~50s) e aguardamos completar 60s antes do próximo bloco.
        $chunks = collect($paraNotificar)->chunk(100);
        foreach ($chunks as $chunkIndex => $chunk) {
            $base = Carbon::now()->addSeconds($chunkIndex * 60);
            foreach ($chunk->values() as $i => [$email, $nome, $acao, $certId]) {
                // Dois e-mails por segundo: delay incremental a cada par.
                $pairDelay = intdiv($i, 2); // 0,0,1,1,2,2...
                $scheduleAt = $base->copy()->addSeconds($pairDelay);
                Mail::to($email)->later($scheduleAt, new CertificadoEmitidoMail($nome, $acao, $certId));
            }
        }
    }

    public function emitirPorParticipantes(Request $request)
    {
        $data = $request->validate([
            'modelo_id'        => ['required', 'exists:modelo_certificados,id'],
            'participantes'    => ['sometimes', 'array'],
            'participantes.*'  => ['integer'],
            'select_all_pages' => ['sometimes', 'boolean'],
        ]);

        $modelo = ModeloCertificado::findOrFail($data['modelo_id']);
        $participantesIds = array_unique(array_filter($data['participantes'] ?? []));
        $selectAllPages = (bool) ($data['select_all_pages'] ?? false);

        // Busca presen?as pendentes, filtrando se houver sele??o
        $presencasPendentes = Presenca::with(['atividade.evento', 'inscricao.participante.user'])
            ->where('status', 'presente')
            ->where('certificado_emitido', false)
            ->when(!empty($participantesIds) && ! $selectAllPages, function ($q) use ($participantesIds) {
                $q->whereHas('inscricao.participante', fn ($sub) => $sub->whereIn('id', $participantesIds));
            })
            ->whereHas('inscricao.participante') // garante participante
            ->get()
            ->filter(fn ($p) => $p->atividade?->evento); // garante evento carregado

        $created = 0;
        $paraNotificar = [];

        // Agrupa por participante para emitir um cert por evento
        $presencasPorParticipante = $presencasPendentes->groupBy(fn ($p) => $p->inscricao->participante->id);

        foreach ($presencasPorParticipante as $participanteId => $presencasDoParticipante) {
            $participante = $presencasDoParticipante->first()->inscricao->participante;
            if (! $participante || ! $participante->user) {
                continue;
            }

            $presencasPorEvento = $presencasDoParticipante->groupBy(fn ($p) => $p->atividade->evento_id);

            foreach ($presencasPorEvento as $eventoId => $presencasEvento) {
                $evento = $presencasEvento->first()->atividade->evento;
                if (! $evento) {
                    continue;
                }

                $cargaTotal = $presencasEvento->sum(function ($p) {
                    return (float) ($p->atividade?->carga_horaria ?? 0);
                });

                $map = [
                    '%participante%'   => $participante->user->name,
                    '%acao%'           => $evento->nome,
                    '%carga_horaria%'  => $cargaTotal,
                ];

                $textoFrente = $this->renderPlaceholders($modelo->texto_frente ?? '', $map);
                $textoVerso  = $this->renderPlaceholders($modelo->texto_verso ?? '', $map);

                $cert = Certificado::create([
                    'modelo_certificado_id' => $modelo->id,
                    'participante_id'       => $participante->id,
                    'evento_nome'           => $evento->nome,
                    'codigo_validacao'      => Str::uuid()->toString(),
                    'ano'                   => (int) ($evento->data_inicio ? date('Y', strtotime($evento->data_inicio)) : date('Y')),
                    'texto_frente'          => $textoFrente,
                    'texto_verso'           => $textoVerso,
                    'carga_horaria'         => $cargaTotal,
                ]);
                if (!empty($participante->user?->email)) {
                    $paraNotificar[] = [$participante->user->email, $participante->user->name, $evento->nome, $cert->id];
                }

                foreach ($presencasEvento as $presenca) {
                    $presenca->certificado_emitido = true;
                    $presenca->save();
                }

                $created++;
            }
        }

        $this->notificarLote($paraNotificar);

        return redirect()
            ->back()
            ->with('success', "{$created} certificado(s) emitidos com sucesso.");
    }

    private function renderPlaceholders(string $texto, array $map): string
    {
        return strtr($texto, $map);
    }

    public function show(Certificado $certificado)
    {
        $user = auth()->user();
        $isOwner = $certificado->participante_id === optional($user->participante)->id;
        $isAdmin = $user->hasAnyRole(['administrador', 'gestor']);
        if (! $isOwner && ! $isAdmin) {
            abort(403);
        }
        $certificado->load('modelo');

        return view('certificados.show', compact('certificado'));
    }

    public function validar(string $codigo)
    {
        $certificado = Certificado::with('modelo', 'participante.user')
            ->where('codigo_validacao', $codigo)
            ->firstOrFail();

        return view('certificados.validacao', compact('certificado'));
    }

    public function download(Certificado $certificado)
    {
        $user = auth()->user();
        $isOwner = $certificado->participante_id === optional($user->participante)->id;
        $isAdmin = $user->hasAnyRole(['administrador', 'gestor']);
        if (! $isOwner && ! $isAdmin) {
            abort(403);
        }

        $certificado->load('modelo');
        $pdf = app('dompdf.wrapper');
        // Reduz DPI para gerar arquivo menor (objetivo ~1MB) e permite imagens remotas
        $pdf->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'dpi' => 72,
            'defaultMediaType' => 'print',
        ]);
        $pdf->setPaper('a4', 'landscape');
        $pdf->loadView('certificados.pdf', ['certificado' => $certificado]);
        $fileName = 'certificado-'.$certificado->id.'.pdf';

        return $pdf->download($fileName);
    }

    public function emitidos()
    {
        $certificados = Certificado::with(['participante.user', 'modelo'])
            ->latest()
            ->paginate(20);

        return view('certificados.emitidos', compact('certificados'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'modelo_id' => ['required', 'exists:modelo_certificados,id'],
            'eventos'   => ['nullable', 'string'],
        ]);

        $modelo = ModeloCertificado::findOrFail($request->modelo_id);
        $eventos = collect(explode(',', (string) $request->eventos))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $eventoNome = 'Ação pedagógica';
        if ($eventos->count()) {
            $evento = Evento::find($eventos->first());
            if ($evento) {
                $eventoNome = $evento->nome;
            }
        }

        $map = [
            '%participante%'  => '[NOME DO PARTICIPANTE]',
            '%acao%'          => '[NOME DA AÇÃO PEDAGÓGICA]',
            '%carga_horaria%' => '10',
        ];

        $certificado = new Certificado();
        $certificado->modelo = $modelo;
        $certificado->texto_frente = strtr($modelo->texto_frente ?? '', $map);
        $certificado->texto_verso  = strtr($modelo->texto_verso ?? '', $map);
        $certificado->evento_nome  = $eventoNome;
        $certificado->codigo_validacao = null;
        $certificado->carga_horaria = 10;

        $pdf = app('dompdf.wrapper');
        $pdf->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'dpi' => 72,
            'defaultMediaType' => 'print',
        ]);
        $pdf->setPaper('a4', 'landscape');
        $pdf->loadView('certificados.pdf', ['certificado' => $certificado]);

        return $pdf->stream('certificado-preview.pdf');
    }
}
