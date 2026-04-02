<?php

namespace App\Pdf\ListaDePresenca\Strategies;

use App\Models\Atividade;
use App\Pdf\ListaDePresenca\ListaPresencaOficinaPdf;
use Exception;
use Illuminate\Support\Collection;

class ListaPresencaOficinaStrategy implements ListaPresencaStrategyInterface
{
    public function gerarPdf(Atividade $atividade, Collection $participantes): string
    {
        //caminho para o template base
        $templatePath = storage_path('app/templates/base_lista_presenca_oficina.pdf');

        if (!file_exists($templatePath)) {
            throw new Exception('O template base em PDF para a Oficina não foi encontrado.');
        }

        $pdf = new ListaPresencaOficinaPdf('L');
        $pdf->setBaseTemplate($templatePath);

        //preenche o cabeçalho do template
        $municipioAtividade = $atividade->municipio;
        $pdf->municipioLabel = $municipioAtividade ? ($municipioAtividade->nome . ' - ' . ($municipioAtividade->estado->sigla ?? '')) : '—';
        $pdf->dataLabel = \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y');

        //margens
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 30);
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 7.5);

        $pdf->SetLineWidth(0.4);

        $contador = 1;

        if ($participantes->isEmpty()) {
            $pdf->Cell(278, 8, utf8_decode('Nenhum participante inscrito neste momento.'), 1, 1, 'C');
        } else {
            foreach ($participantes as $participante) {
                $user = $participante->user;

                //nome
                $nome = utf8_decode($user->name ?? '');
                while ($pdf->GetStringWidth($nome) > 74) {
                    $nome = substr($nome, 0, -1);
                }

                //vinculo(tag), marca com X o respectivo vinculo
                $tagVinculo = mb_strtolower(trim($participante->tag ?? ''));
                $marcaRede      = ($tagVinculo === 'rede de ensino') ? 'X' : ' ';
                $marcaMovimento = ($tagVinculo === 'movimento social') ? 'X' : ' ';
                $textoVinculo = utf8_decode(" ( {$marcaRede} ) EJA REDE MUNICIPAL\n ( {$marcaMovimento} ) EJA MOVIMENTO SOCIAL");

                //CPF
                $cpfSujo = $participante->cpf ?? '';
                $cpfLimpo = preg_replace('/[^0-9]/', '', $cpfSujo);
                if (strlen($cpfLimpo) === 11) {
                    $cpfFormatado = preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cpfLimpo);
                } else {
                    $cpfFormatado = $cpfSujo ?: '';
                }
                $cpf = utf8_decode($cpfFormatado);

                //e-mail
                $email = utf8_decode($user->email ?? '');
                while ($pdf->GetStringWidth($email) > 69) {
                    $email = substr($email, 0, -1);
                }

                $pdf->Cell(8, 8, $contador++, 1, 0, 'C');
                $pdf->Cell(75, 8, $nome, 1, 0, 'L');

                //linha especifica para vinculo que tem a logica diferente
                $xAntesVinculo = $pdf->GetX();
                $yAntesVinculo = $pdf->GetY();
                $pdf->MultiCell(50, 4, $textoVinculo, 1, 'C');
                $pdf->SetXY($xAntesVinculo + 50, $yAntesVinculo);

                $pdf->Cell(35, 8, $cpf, 1, 0, 'C');
                $pdf->Cell(70, 8, $email, 1, 0, 'C');
                $pdf->Cell(40, 8, '', 1, 1, 'C'); // 5. Assinatura em branco
            }
        }

        return $pdf->Output('S');
    }
}
