<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CertificadoEmitidoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $nome;
    public string $acao;
    public int $certificadoId;
    public ?string $logoData = null;

    public function __construct(string $nome, string $acao, int $certificadoId)
    {
        $this->nome = $nome;
        $this->acao = $acao;
        $this->certificadoId = $certificadoId;
    }

    public function build(): self
    {
        $logoPath = public_path('images/logo-aeb.png');

        return $this->subject('Projeto ALFA-EJA Brasil - Certificado disponível!')
            ->view('emails.certificados.emitido')
            ->with(['logoPath' => $logoPath]);
    }
}
