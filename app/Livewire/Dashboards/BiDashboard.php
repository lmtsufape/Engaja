<?php

namespace App\Livewire\Dashboards;

use App\Models\BiValor;
use App\Models\Municipio;
use Livewire\Attributes\Url;
use Livewire\Component;

class BiDashboard extends Component
{
    public array $anosDisponiveis = [];
    public array $municipiosDisponiveis = [];

    #[Url(nullable: true)]
    public $ano = null;

    #[Url(as: 'municipio_id', nullable: true)]
    public $municipioId = null;

    public string $indicadorDimensoes = 'ANALFABETISMO_QTDE';

    public function mount(): void
    {
        $this->carregarAnosDisponiveis();

        $anoPadrao = $this->anosDisponiveis[0] ?? (int) now()->year;
        $anoInformado = (int) ($this->ano ?? 0);
        $this->ano = in_array($anoInformado, $this->anosDisponiveis, true)
            ? $anoInformado
            : $anoPadrao;

        $this->carregarMunicipiosDisponiveis();

        $this->normalizarMunicipioSelecionado();
    }

    public function updatedAno($valor): void
    {
        $anoInformado = (int) $valor;

        $this->ano = in_array($anoInformado, $this->anosDisponiveis, true)
            ? $anoInformado
            : ($this->anosDisponiveis[0] ?? (int) now()->year);

        $this->carregarMunicipiosDisponiveis();
        $this->normalizarMunicipioSelecionado();
    }

    public function updatedMunicipioId($valor): void
    {
        $this->municipioId = $valor;
        $this->normalizarMunicipioSelecionado();
    }

    private function carregarAnosDisponiveis(): void
    {
        $this->anosDisponiveis = BiValor::query()
            ->select('ano')
            ->distinct()
            ->orderByDesc('ano')
            ->pluck('ano')
            ->map(fn ($ano) => (int) $ano)
            ->values()
            ->all();
    }

    private function carregarMunicipiosDisponiveis(): void
    {
        $this->municipiosDisponiveis = Municipio::query()
            ->join('bi_valores', 'bi_valores.municipio_id', '=', 'municipios.id')
            ->join('bi_indicadores', 'bi_indicadores.id', '=', 'bi_valores.indicador_id')
            ->where('bi_indicadores.codigo', $this->indicadorDimensoes)
            ->where('bi_valores.ano', (int) $this->ano)
            ->whereNotNull('bi_valores.dimensao_valor_id')
            ->select('municipios.id', 'municipios.nome')
            ->distinct()
            ->orderBy('municipios.nome')
            ->get()
            ->map(fn ($municipio) => [
                'id' => (int) $municipio->id,
                'nome' => (string) $municipio->nome,
            ])
            ->values()
            ->all();
    }

    private function normalizarMunicipioSelecionado(): void
    {
        $municipioId = $this->municipioIdNormalizado();

        if ($municipioId === null) {
            $this->municipioId = null;
            return;
        }

        $municipioExiste = collect($this->municipiosDisponiveis)
            ->contains(fn (array $municipio) => (int) $municipio['id'] === $municipioId);

        $this->municipioId = $municipioExiste ? $municipioId : null;
    }

    public function municipioIdNormalizado(): ?int
    {
        $id = (int) ($this->municipioId ?? 0);
        return $id > 0 ? $id : null;
    }

    public function municipioSelecionadoNome(): ?string
    {
        $municipioId = $this->municipioIdNormalizado();
        if ($municipioId === null) {
            return null;
        }

        $municipio = collect($this->municipiosDisponiveis)
            ->first(fn (array $item) => (int) $item['id'] === $municipioId);

        return is_array($municipio) ? $municipio['nome'] : null;
    }

    public function render()
    {
        return view('livewire.dashboards.bi-dashboard');
    }
}
