<?php

namespace App\Notifications\Cartas;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class CartasVerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Cartas para Esperançar - Confirme seu e-mail.')
            ->view('emails.cartas.verify-email', [
                'userName' => $notifiable->name,
                'url' => $url,
            ]);
    }

    /**
     * Assina a rota do Cartas em vez da rota do Engaja herdada do pai: o link
     * precisa morar sob /cartas para que qualquer redirecionamento derivado
     * dele (login, aviso de verificação) fique no sistema certo.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'cartas.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
