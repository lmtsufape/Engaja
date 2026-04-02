<?php

namespace App\Pdf\ListaDePresenca;

use setasign\Fpdi\Fpdi;

class ListaPresencaOficinaPdf extends Fpdi
{
    protected $templatePage1;
    protected $templatePage2;

    public $municipioLabel;
    public $dataLabel;

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

            $this->Text(37.6, 65.7, utf8_decode($this->municipioLabel));
            $this->Text(254, 65.7, utf8_decode($this->dataLabel));

            //posição Y onde começa a tabela na pagina 1
            $this->SetY(82);
        } else {
            $this->useTemplate($this->templatePage2, 0, 0, null, null, true);

            //posição Y onde começa a tabela na pagina 2
            $this->SetY(54);
        }

        $this->SetFont('Helvetica', 'B', 9);

        //cabecalhos da tabela
        $this->Cell(8, 8, utf8_decode('Nº'), 1, 0, 'C');
        $this->Cell(75, 8, utf8_decode('NOME COMPLETO'), 1, 0, 'C');
        $this->Cell(50, 8, utf8_decode('VÍNCULO'), 1, 0, 'C');
        $this->Cell(35, 8, utf8_decode('CPF'), 1, 0, 'C');
        $this->Cell(70, 8, utf8_decode('E-MAIL'), 1, 0, 'C');
        $this->Cell(40, 8, utf8_decode('ASSINATURA'), 1, 1, 'C');
    }
}
