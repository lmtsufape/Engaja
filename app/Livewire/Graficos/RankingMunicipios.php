<?php

namespace App\Livewire\Graficos;

use Livewire\Component;
use App\Repositories\BiValorRepository;

// Para ser usado com taxas
class RankingMunicipios extends Component
{
    public array $dados = [];
    public int $ano;
    public string $indicador;
    public string $titulo;

    public function mount(BiValorRepository $repository)
    {
        $resultado = $repository->rankingMunicipios($this->indicador, $this->ano);
        $this->dados = $resultado['dados'];
    }

    public function render()
    {
        return view('livewire.graficos.ranking-municipios');
    }
}
