<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class EmailVerification extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Zweryfikuj swój adres e-mail')
            ->greeting('Witaj!')
            ->line('Kliknij przycisk poniżej, aby zweryfikować swój adres e-mail.')
            ->action('Zweryfikuj adres e-mail', $verificationUrl)
            ->line('Jeśli nie utworzyłeś konta, zignoruj tę wiadomość.')
            ->salutation('Pozdrawiamy,');
    }

    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
