<?php

namespace App\Pdf\ListaDePresenca;

use App\Pdf\ListaDePresenca\Strategies\ListaPresencaAssessoriaStrategy;
use App\Pdf\ListaDePresenca\Strategies\ListaPresencaOficinaStrategy;
use App\Pdf\ListaDePresenca\Strategies\ListaPresencaStrategyInterface;
use Exception;

class ListaPresencaFactory
{
    public static function criar(string $tipo): ListaPresencaStrategyInterface
    {
        return match ($tipo) {
            //quando for necessario um template noivo, apenas adicionar: 'outro_tipo' => new OutroTemplateStrategy(),
            'assessoria' => new ListaPresencaAssessoriaStrategy(),
            'oficina'    => new ListaPresencaOficinaStrategy(),
            default => throw new Exception("Tipo de template de lista de presença inválido: {$tipo}"),
        };
    }
}
