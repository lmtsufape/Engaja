<?php

namespace App\Livewire\Graficos;

use App\Repositories\BiValorRepository;
use Livewire\Component;

class DistribuicaoDimensao extends Component
{
    public array $dados = [];
    public int $ano;
    public string $indicador;
    public string $dimensao;
    public ?int $municipioId = null;
    public string $titulo = 'Distribuicao por dimensao';
    public string $tipoValor = 'ABSOLUTO';
    public string $tipoGrafico = 'donut';
    public bool $mostrarValores = true;
    public bool $usarPercentual = false;
    public int $casasDecimaisPercentual = 2;

    public function mount(BiValorRepository $repository): void
    {
        $resultado = $repository->distribuicaoPorDimensao(
            $this->indicador,
            $this->ano,
            $this->dimensao,
            $this->municipioId
        );

        $this->dados = $resultado['dados'] ?? [];
        $this->tipoValor = $resultado['tipo_valor'] ?? 'ABSOLUTO';
    }

    public function render()
    {
        return view('livewire.graficos.distribuicao-dimensao');
    }
}
