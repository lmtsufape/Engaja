<?php

namespace App\Pdf\ListaDePresenca\Strategies;

use App\Models\Atividade;
use Illuminate\Support\Collection;

interface ListaPresencaStrategyInterface
{
    public function gerarPdf(Atividade $atividade, Collection $participantes): string;
}
