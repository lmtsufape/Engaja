<?php

namespace App\Pdf\ListaDePresenca;

use setasign\Fpdi\Fpdi;

class ListaPresencaAssessoriaPdf extends Fpdi
{
    protected $templatePage1;
    protected $templatePage2;

    public $municipioLabel;
    public $dataLabel;
    public $atividadeLabel;
    public $periodoLabel;

    public function setBaseTemplate($caminhoPdf)
    {
        $this->setSourceFile($caminhoPdf);
        $this->templatePage1 = $this->importPage(1);
        $this->templatePage2 = $this->importPage(2);
    }

    public function Header()
    {
        if ($this->PageNo() == 1) {
            $this->useTemplate($this->templatePage1, 0, 0, null, null, true);
            $this->SetFont('Helvetica', 'B', 10);

            $this->SetLineWidth(0.4);

            $this->Text(32, 55, utf8_decode($this->municipioLabel));
            $this->Text(190, 55, utf8_decode($this->dataLabel));

            $atividade = utf8_decode($this->atividadeLabel);
            $limiteAtividade = 145;
            if ($this->GetStringWidth($atividade) > $limiteAtividade) {
                while ($this->GetStringWidth($atividade . '...') > $limiteAtividade) {
                    $atividade = substr($atividade, 0, -1);
                }
                $atividade .= '...';
            }
            $this->Text(32, 62, $atividade);
            $this->Text(196, 62, utf8_decode($this->periodoLabel));

            $this->SetY(75);
        } else {
            $this->useTemplate($this->templatePage2, 0, 0, null, null, true);
            $this->SetY(45);
        }

        $this->SetFont('Helvetica', 'B', 9);

        $this->Cell(8, 8, utf8_decode('Nº'), 1, 0, 'C');
        $this->Cell(75, 8, utf8_decode('Nome do Participante'), 1, 0, 'C');
        $this->Cell(35, 8, utf8_decode('CPF'), 1, 0, 'C');
        $this->Cell(50, 8, utf8_decode('Município'), 1, 0, 'C');
        $this->Cell(70, 8, utf8_decode('E-mail'), 1, 0, 'C');
        $this->Cell(40, 8, utf8_decode('Assinatura'), 1, 1, 'C');
    }
}
