<?php

namespace App\Exports;

use App\Models\Evento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EventoParticipantesPorMomentoExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    private Evento $evento;

    public function __construct(Evento $evento)
    {
        $this->evento = $evento;
    }

    public function collection(): Collection
    {
        return $this->baseQuery()
            ->orderBy('atividades.dia')
            ->orderBy('atividades.hora_inicio')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Ação pedagógica',
            'Momento',
            'Nome',
            'Email',
            'CPF',
            'Telefone',
            'Município',
            'Organização',
            'Tipo de organização',
            'Tag',
        ];
    }

    public function map($row): array
    {
        return [
            $this->evento->nome,
            $row->momento,
            $row->nome,
            $row->email,
            $row->cpf,
            $row->telefone,
            $this->formatMunicipioEstado($row->municipio, $row->estado),
            $row->escola_unidade,
            $row->tipo_organizacao,
            $row->tag,
        ];
    }

    private function baseQuery()
    {
        return DB::table('presencas')
            ->join('inscricaos', 'inscricaos.id', '=', 'presencas.inscricao_id')
            ->join('atividades', 'atividades.id', '=', 'presencas.atividade_id')
            ->join('participantes', 'participantes.id', '=', 'inscricaos.participante_id')
            ->leftJoin('users', 'users.id', '=', 'participantes.user_id')
            ->leftJoin('municipios', 'municipios.id', '=', 'participantes.municipio_id')
            ->leftJoin('estados', 'estados.id', '=', 'municipios.estado_id')
            ->where('atividades.evento_id', $this->evento->id)
            ->where('presencas.status', 'presente')
            ->whereNull('presencas.deleted_at')
            ->whereNull('inscricaos.deleted_at')
            ->select([
                'atividades.id as atividade_id',
                'atividades.descricao as momento',
                'users.name as nome',
                'users.email',
                'participantes.cpf',
                'participantes.telefone',
                'municipios.nome as municipio',
                'estados.sigla as estado',
                'participantes.escola_unidade',
                'participantes.tipo_organizacao',
                'participantes.tag',
            ]);
    }

    private function formatMunicipioEstado(?string $municipio, ?string $estado): ?string
    {
        if (!$municipio && !$estado) {
            return null;
        }

        if ($municipio && $estado) {
            return $municipio . ' - ' . $estado;
        }

        return $municipio ?: $estado;
    }
}
