<?php

namespace App\Livewire\Graficos;

use Livewire\Component;
use App\Repositories\BiValorRepository;

// Para ser usado com taxas
class RankingMunicipios extends Component
{
    public array $dados = [];
    public int $ano;
    public ?string $indicador = null;
    public ?string $indicadorPercentual = null;
    public ?string $indicadorAbsoluto = null;
    public string $titulo = 'Ranking de Municipios';
    public string $tipoValor = 'PERCENTUAL';
    public string $labelIndicadorPercentual = 'Taxa';
    public string $labelIndicadorAbsoluto = 'Quantidade';
    public string $cor = '--engaja-purple';
    public array $resumo = [
        'total_absoluto' => 0.0,
        'media_percentual' => 0.0,
        'municipios' => 0,
    ];

    public function mount(BiValorRepository $repository): void
    {
        if (!empty($this->indicadorPercentual) && !empty($this->indicadorAbsoluto)) {
            $resultado = $repository->rankingMunicipiosComIndicadores(
                $this->indicadorPercentual,
                $this->indicadorAbsoluto,
                $this->ano
            );

            $this->dados = $resultado['dados'] ?? [];
            $this->labelIndicadorPercentual = $resultado['indicador_percentual']['label'] ?? $this->labelIndicadorPercentual;
            $this->labelIndicadorAbsoluto = $resultado['indicador_absoluto']['label'] ?? $this->labelIndicadorAbsoluto;
            $this->resumo = $resultado['resumo'] ?? $this->resumo;

            return;
        }

        if (empty($this->indicador)) {
            $this->dados = [];
            return;
        }

        $resultado = $repository->rankingMunicipios($this->indicador, $this->ano);
        $this->dados = $resultado['dados'] ?? [];
        $this->tipoValor = $resultado['tipo_valor'] ?? 'PERCENTUAL';
    }

    public function render()
    {
        return view('livewire.graficos.ranking-municipios');
    }
}
