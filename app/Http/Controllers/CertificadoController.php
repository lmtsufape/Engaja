<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Evento;
use App\Models\ModeloCertificado;
use App\Models\Presenca;
use App\Models\Participante;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            return back()->with('error', 'Selecione ao menos uma a??o pedag?gica.');
        }

        $modelo = ModeloCertificado::findOrFail($data['modelo_id']);

        $eventos = Evento::with(['presencas.inscricao.participante.user', 'presencas.atividade'])
            ->whereIn('id', $eventosIds)
            ->get();

        $created = 0;
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

                Certificado::create([
                    'modelo_certificado_id' => $modelo->id,
                    'participante_id'       => $participante->id,
                    'codigo_validacao'      => Str::uuid()->toString(),
                    'ano'                   => (int) ($evento->data_inicio ? date('Y', strtotime($evento->data_inicio)) : date('Y')),
                    'texto_frente'          => $textoFrente,
                    'texto_verso'           => $textoVerso,
                    'carga_horaria'         => $cargaTotal,
                ]);

                // Marca todas as presen?as deste participante no evento como certificadas
                foreach ($presencas as $presenca) {
                    $presenca->certificado_emitido = true;
                    $presenca->save();
                }

                $created++;
            }
        }

        return redirect()
            ->back()
            ->with('success', "{$created} certificado(s) emitidos com sucesso.");
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

                Certificado::create([
                    'modelo_certificado_id' => $modelo->id,
                    'participante_id'       => $participante->id,
                    'codigo_validacao'      => Str::uuid()->toString(),
                    'ano'                   => (int) ($evento->data_inicio ? date('Y', strtotime($evento->data_inicio)) : date('Y')),
                    'texto_frente'          => $textoFrente,
                    'texto_verso'           => $textoVerso,
                    'carga_horaria'         => $cargaTotal,
                ]);

                foreach ($presencasEvento as $presenca) {
                    $presenca->certificado_emitido = true;
                    $presenca->save();
                }

                $created++;
            }
        }

        return redirect()
            ->back()
            ->with('success', "{$created} certificado(s) emitidos com sucesso.");
    }

    private function renderPlaceholders(string $texto, array $map): string
    {
        return strtr($texto, $map);
    }
}
