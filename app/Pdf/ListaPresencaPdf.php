<?php

namespace App\Pdf;

use setasign\Fpdi\Fpdi;

class ListaPresencaPdf extends Fpdi
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
            //apenas o template da primeira pagina que possui o cabeçalho
            $this->useTemplate($this->templatePage1, 0, 0, null, null, true);
            $this->SetFont('Helvetica', 'B', 11);


            $this->Text(32, 55, utf8_decode($this->municipioLabel));
            $this->Text(190, 55, utf8_decode($this->dataLabel));

            $this->Text(32, 62, utf8_decode($this->temaLabel));
            $this->Text(196, 62, utf8_decode($this->periodoLabel));

            $this->SetY(75);
        } else {
            //segunda pagina em diante, que nao possui cabecalho
            $this->useTemplate($this->templatePage2, 0, 0, null, null, true);
            $this->SetY(45);
        }

        $this->SetFont('Helvetica', 'B', 9);

        $this->Cell(8, 8, utf8_decode('Nº'), 1, 0, 'C');
        $this->Cell(80, 8, utf8_decode('Nome do Participante'), 1, 0, 'C');
        $this->Cell(65, 8, utf8_decode('Instituição'), 1, 0, 'C');
        $this->Cell(45, 8, utf8_decode('CPF'), 1, 0, 'C');
        $this->Cell(45, 8, utf8_decode('E-mail / Telefone'), 1, 0, 'C');
        $this->Cell(35, 8, utf8_decode('Assinatura'), 1, 1, 'C');

    }
}
