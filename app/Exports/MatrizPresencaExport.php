<?php

namespace App\Exports;

use App\Models\Evento;
use App\Models\Atividade;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MatrizPresencaExport implements WithMultipleSheets
{
    use Exportable;

    protected $eventoId;

    public function __construct($eventoId)
    {
        $this->eventoId = $eventoId;
    }

    public function sheets(): array
    {
        $evento = Evento::findOrFail($this->eventoId);

        $atividades = Atividade::where('evento_id', $this->eventoId)
            ->whereNull('deleted_at')
            ->with([
                'municipio',
                'inscricoes' => fn ($q) => $q->whereNull('deleted_at')->with('participante.user'),
                'presencas' => fn ($q) => $q->where('status', 'presente')->whereNull('deleted_at'),
            ])
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        //agrupa os momentos por municipio
        $atividadesPorMunicipio = $atividades->groupBy('municipio_id');

        $sheets = [];

        //capa
        $sheets[] = new MatrizPresencaCapaSheet($evento, $atividadesPorMunicipio);

        //uma aba por municipio
        foreach ($atividadesPorMunicipio as $municipioId => $municipioAtividades) {
            $sheets[] = new MatrizPresencaMunicipioSheet($municipioId, $municipioAtividades);
        }

        return $sheets;
    }
}
