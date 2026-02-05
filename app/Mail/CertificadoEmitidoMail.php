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
        $logoPath = public_path('images/engaja-bg-white.png');
        if (file_exists($logoPath)) {
            $data = base64_encode(file_get_contents($logoPath));
            $this->logoData = 'data:image/png;base64,'.$data;
        }

        return $this->subject('Certificado disponivel - '.$this->acao)
            ->view('emails.certificados.emitido')
            ->with(['logoData' => $this->logoData]);
    }
}
