<?php

namespace App\Word;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Wrapper fino sobre PhpWord para gerar documentos .docx editáveis com a
 * identidade institucional Alfa-EJA (cabeçalho/rodapé com logo), espelhando
 * o que o macro withAlfaEjaBrand faz para os PDFs.
 *
 * Reutiliza os mesmos arquivos de imagem do macro de PDF
 * (public/images/Alfa-Eja Header.png e Alfa-Eja Footer.png).
 */
class WordDocument
{
    private const BRAND_COLOR = '421944';

    private PhpWord $phpWord;

    private Section $section;

    public function __construct(private string $orientation = 'portrait')
    {
        $this->phpWord = new PhpWord;
        $this->phpWord->setDefaultFontName('Arial');
        $this->phpWord->setDefaultFontSize(10);

        $landscape = $this->orientation === 'landscape';

        $this->section = $this->phpWord->addSection([
            'orientation' => $landscape ? 'landscape' : 'portrait',
            'marginTop' => Converter::cmToTwip(3.4),
            'marginBottom' => Converter::cmToTwip(2.8),
            'marginLeft' => Converter::cmToTwip(1.6),
            'marginRight' => Converter::cmToTwip(1.6),
            'headerHeight' => Converter::cmToTwip(2.6),
            'footerHeight' => Converter::cmToTwip(1.8),
        ]);

        $this->applyAlfaEjaBrand();
    }

    /**
     * Adiciona cabeçalho (logo, largura total) e rodapé institucionais,
     * repetidos em todas as páginas.
     */
    private function applyAlfaEjaBrand(): void
    {
        $landscape = $this->orientation === 'landscape';

        // Largura útil da página (A4) menos as margens laterais, em pontos.
        $pageWidthCm = $landscape ? 29.7 : 21.0;
        $contentWidthPt = ($pageWidthCm - 3.2) * 28.3465;

        $headerPath = public_path('images/Alfa-Eja Header.png');
        if (is_file($headerPath)) {
            $header = $this->section->addHeader();
            $header->addImage($headerPath, [
                'width' => $contentWidthPt,
                'height' => $contentWidthPt / (1600 / 201),
                'alignment' => Jc::CENTER,
                'wrappingStyle' => 'inline',
            ]);
        }

        $footerPath = public_path('images/Alfa-Eja Footer.png');
        if (is_file($footerPath)) {
            $footer = $this->section->addFooter();
            $footerWidthPt = 6.0 * 28.3465;
            $footer->addImage($footerPath, [
                'width' => $footerWidthPt,
                'height' => $footerWidthPt / (1102 / 344),
                'alignment' => Jc::CENTER,
                'wrappingStyle' => 'inline',
            ]);
        }
    }

    public function addTitle(string $title): self
    {
        $this->section->addText(
            $title,
            ['bold' => true, 'size' => 16, 'color' => self::BRAND_COLOR],
            ['spaceAfter' => 120]
        );

        return $this;
    }

    /**
     * Título de bloco/seção (ex.: uma aba de planilha vira uma seção no Word).
     */
    public function addHeading(string $text): self
    {
        $this->section->addText(
            $text,
            ['bold' => true, 'size' => 13, 'color' => self::BRAND_COLOR],
            ['spaceBefore' => 200, 'spaceAfter' => 80]
        );

        return $this;
    }

    public function addParagraph(string $text, array $fontStyle = []): self
    {
        $this->section->addText($text, $fontStyle, ['spaceAfter' => 80]);

        return $this;
    }

    /**
     * Resumo de filtros aplicados, no topo do documento.
     *
     * @param  array<int, string>  $linhas
     */
    public function addFiltersSummary(array $linhas): self
    {
        $linhas = array_values(array_filter($linhas, fn ($l) => trim((string) $l) !== ''));

        if ($linhas === []) {
            return $this;
        }

        $this->section->addText('Filtros aplicados:', ['bold' => true, 'size' => 9], ['spaceAfter' => 0]);

        foreach ($linhas as $linha) {
            $this->section->addText($linha, ['size' => 9, 'color' => '555555'], ['spaceAfter' => 0]);
        }

        $this->section->addTextBreak(1);

        return $this;
    }

    /**
     * Adiciona uma tabela com cabeçalho destacado.
     *
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function addTable(array $headings, iterable $rows): self
    {
        $table = $this->section->addTable([
            'borderSize' => 6,
            'borderColor' => 'D5D5D5',
            'cellMargin' => 60,
            'width' => 5000,
            'unit' => 'pct',
        ]);

        if ($headings !== []) {
            $table->addRow(null, ['tblHeader' => true]);
            foreach ($headings as $heading) {
                $cell = $table->addCell(null, ['bgColor' => self::BRAND_COLOR, 'valign' => 'center']);
                $cell->addText((string) $heading, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9], ['spaceAfter' => 0]);
            }
        }

        foreach ($rows as $row) {
            $table->addRow();
            foreach ((array) $row as $value) {
                $cell = $table->addCell(null, ['valign' => 'center']);
                $cell->addText($this->stringify($value), ['size' => 9], ['spaceAfter' => 0]);
            }
        }

        $this->section->addTextBreak(1);

        return $this;
    }

    public function addTextBreak(int $count = 1): self
    {
        $this->section->addTextBreak($count);

        return $this;
    }

    public function getSection(): Section
    {
        return $this->section;
    }

    public function download(string $filename): StreamedResponse
    {
        $writer = IOFactory::createWriter($this->phpWord, 'Word2007');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }
}
