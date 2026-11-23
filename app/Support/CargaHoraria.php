<?php

namespace App\Support;

/**
 * Carga horária persistida em minutos inteiros (colunas carga_horaria em atividades e certificados).
 */
final class CargaHoraria
{
    /**
     * Formata minutos para exibição (ex.: 2h, 1h 30min, 45min).
     */
    public static function formatMinutos(?int $minutos): string
    {
        if ($minutos === null || $minutos < 0) {
            return '—';
        }
        if ($minutos === 0) {
            return '0min';
        }

        $h = intdiv($minutos, 60);
        $m = $minutos % 60;

        if ($h > 0 && $m === 0) {
            return $h.'h';
        }
        if ($h > 0) {
            return $h.'h '.$m.'min';
        }

        return $m.'min';
    }

    /**
     * formata minutos para uso em certificados, sem espaço entre horas e minutos
     * tipo 0h, 1h30min, 45min, etc
     */
    public static function formatMinutosCompacto(?int $minutos): string
    {
        if ($minutos === null || $minutos < 0) {
            return '—';
        }
        if ($minutos === 0) {
            return '0min';
        }

        $h = intdiv($minutos, 60);
        $m = $minutos % 60;

        if ($h > 0 && $m === 0) {
            return $h.'h';
        }
        if ($h > 0) {
            return $h.'h'.$m.'min';
        }

        return $m.'min';
    }

    /**
     * Converte horas e minutos de formulário em total de minutos; null se ambos forem zero.
     */
    public static function totalMinutosFromPartes(int $horas, int $minutos): ?int
    {
        $total = $horas * 60 + $minutos;

        return $total > 0 ? $total : null;
    }
}
