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

    public function __construct(string $nome, string $acao, int $certificadoId)
    {
        $this->nome = $nome;
        $this->acao = $acao;
        $this->certificadoId = $certificadoId;
    }

    public function build(): self
    {
        return $this->subject('Certificado disponível - '.$this->acao)
            ->view('emails.certificados.emitido');
    }
}
