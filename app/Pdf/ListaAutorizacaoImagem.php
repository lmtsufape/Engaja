<?php

namespace App\Pdf;

use setasign\Fpdi\Fpdi;

class ListaAutorizacaoImagem extends Fpdi
{
    protected $templatePage1;
    protected $templatePage2;

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
            $this->SetFont('Helvetica', 'B', 11);

            $this->SetY(188);

        } else {
            $this->useTemplate($this->templatePage2, 0, 0, null, null, true);

            $this->SetY(45);
        }

        $this->SetFont('Helvetica', 'B', 9);

        $this->Cell(8, 8, utf8_decode('Nº'), 1, 0, 'C');
        $this->Cell(90, 8, utf8_decode('Nome do Participante'), 1, 0, 'C');
        $this->Cell(45, 8, utf8_decode('CPF'), 1, 0, 'C');
        $this->Cell(47, 8, utf8_decode('Assinatura'), 1, 1, 'C');
    }
}
