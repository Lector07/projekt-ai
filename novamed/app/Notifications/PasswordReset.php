<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class PasswordReset extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = url('/reset-password/' . $this->token . '?email=' . $notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Zresetuj swoje hasło')
            ->greeting('Witaj!')
            ->line('Otrzymujesz tę wiadomość, ponieważ otrzymaliśmy prośbę o zresetowanie hasła dla Twojego konta.')
            ->action('Zresetuj hasło', $url)
            ->line('Ten link do resetowania hasła wygaśnie za :count minut.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')])
            ->line('Jeśli nie prosiłeś o zresetowanie hasła, zignoruj tę wiadomość.')
            ->salutation('Pozdrawiamy,');
    }
}
