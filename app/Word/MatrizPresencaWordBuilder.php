<?php

namespace App\Word;

use App\Models\Atividade;
use App\Models\Evento;
use Illuminate\Support\Carbon;

/**
 * Monta a Matriz de Presença de uma Ação Pedagógica como documento Word.
 *
 * Espelha a lógica de agregação de MatrizPresencaExport / MatrizPresencaMunicipioSheet
 * (que usam FromView e por isso não passam pelo WordTableExport genérico):
 * uma seção "Resumo" e uma seção por município, cada uma com a matriz de presença.
 */
class MatrizPresencaWordBuilder
{
    public static function build(int $eventoId): WordDocument
    {
        $evento = Evento::findOrFail($eventoId);

        $atividades = Atividade::where('evento_id', $eventoId)
            ->whereNull('deleted_at')
            ->with([
                'municipio',
                'inscricoes' => fn ($q) => $q->whereNull('deleted_at')->with('participante.user'),
                'presencas' => fn ($q) => $q->where('status', 'presente')->whereNull('deleted_at'),
            ])
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        $atividadesPorMunicipio = $atividades->groupBy('municipio_id');

        $doc = new WordDocument('landscape');
        $doc->addTitle('Matriz de Presença — '.($evento->nome ?? ''));

        self::addResumo($doc, $atividadesPorMunicipio);

        foreach ($atividadesPorMunicipio as $municipioAtividades) {
            self::addMunicipio($doc, $municipioAtividades);
        }

        return $doc;
    }

    private static function addResumo(WordDocument $doc, $atividadesPorMunicipio): void
    {
        $doc->addHeading('Resumo por município');

        $linhas = [];

        foreach ($atividadesPorMunicipio as $atividades) {
            $nome = $atividades->first()->municipio?->nome_com_estado ?? 'Sem Município';

            $participantesUnicos = collect();
            foreach ($atividades as $atividade) {
                foreach ($atividade->inscricoes as $inscricao) {
                    $participantesUnicos->push($inscricao->participante_id);
                }
            }

            $linhas[] = [$nome, $atividades->count(), $participantesUnicos->unique()->count()];
        }

        usort($linhas, fn ($a, $b) => strcmp((string) $a[0], (string) $b[0]));

        $doc->addTable(['Município', 'Momentos', 'Participantes únicos'], $linhas);
    }

    private static function addMunicipio(WordDocument $doc, $atividades): void
    {
        $municipioNome = $atividades->first()->municipio?->nome ?? 'Sem Município';

        $participantes = [];

        foreach ($atividades as $atividade) {
            $presentesIds = $atividade->presencas->pluck('inscricao_id');

            foreach ($atividade->inscricoes as $inscricao) {
                $partId = $inscricao->participante_id;

                if (! isset($participantes[$partId])) {
                    $participantes[$partId] = [
                        'nome' => $inscricao->participante?->user?->name ?? 'Participante #'.$partId,
                        'usuario_id' => $inscricao->participante?->user?->id ?? '-',
                        'cpf' => $inscricao->participante?->cpf ?? '-',
                        'email' => $inscricao->participante?->user?->email ?? '-',
                        'vinculo' => $inscricao->participante?->tag ?? '-',
                        'momentos' => [],
                        'presente_count' => 0,
                        'ausente_count' => 0,
                    ];
                }

                if ($presentesIds->contains($inscricao->id)) {
                    $status = ($inscricao->ouvinte ?? false) ? 'Ouvinte' : 'Presente';
                    $participantes[$partId]['presente_count']++;
                } else {
                    $status = 'Ausente';
                    $participantes[$partId]['ausente_count']++;
                }

                $participantes[$partId]['momentos'][$atividade->id] = $status;
            }
        }

        usort($participantes, fn ($a, $b) => strcmp(strtolower($a['nome']), strtolower($b['nome'])));

        $headings = ['Nome', 'ID do usuário', 'CPF', 'E-mail', 'Vínculo'];
        foreach ($atividades as $atividade) {
            $headings[] = self::momentoLabel($atividade);
        }
        $headings[] = 'Presenças';
        $headings[] = 'Ausências';
        $headings[] = 'Total';

        $rows = [];
        foreach ($participantes as $participante) {
            $row = [
                $participante['nome'],
                $participante['usuario_id'],
                $participante['cpf'],
                $participante['email'],
                $participante['vinculo'],
            ];

            foreach ($atividades as $atividade) {
                $row[] = $participante['momentos'][$atividade->id] ?? 'Não Inscrito';
            }

            $row[] = $participante['presente_count'];
            $row[] = $participante['ausente_count'];
            $row[] = $participante['presente_count'] + $participante['ausente_count'];

            $rows[] = $row;
        }

        $doc->addHeading($municipioNome);
        $doc->addTable($headings, $rows);
    }

    private static function momentoLabel(Atividade $atividade): string
    {
        $dia = $atividade->dia ? Carbon::parse($atividade->dia)->format('d/m') : '';
        $descricao = trim((string) $atividade->descricao);

        return trim($dia.' '.$descricao) ?: 'Momento';
    }
}
