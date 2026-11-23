<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CartasResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('cartas.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Cartas para Esperançar - Recuperação de senha.')
            ->view('emails.cartas.reset-password', [
                'userName' => $notifiable->name,
                'url' => $url,
            ]);
    }
}
