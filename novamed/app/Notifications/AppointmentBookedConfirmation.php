<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class AppointmentBookedConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public Appointment $appointment;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $doctorName = $this->appointment->doctor->first_name . ' ' . $this->appointment->doctor->last_name;
        $procedureName = $this->appointment->procedure->name;
        $appointmentDate = Carbon::parse($this->appointment->appointment_datetime)->format('d.m.Y');
        $appointmentTime = Carbon::parse($this->appointment->appointment_datetime)->format('H:i');

        $myAppointmentsUrl = rtrim(env('FRONTEND_URL', 'http://127.0.0.1:8000'), '/') . '/patient/appointments';

        return (new MailMessage)
            ->subject('Potwierdzenie rezerwacji wizyty w NovaMed')
            ->greeting('Witaj ' . $notifiable->name . ',')
            ->line('Dziękujemy za umówienie wizyty w naszej klinice NovaMed. Poniżej znajdują się szczegóły Twojej rezerwacji:')
            ->line("**Numer wizyty:** {$this->appointment->id}")
            ->line("**Lekarz:** Dr {$doctorName}")
            ->line("**Zabieg:** {$procedureName}")
            ->line("**Data:** {$appointmentDate}")
            ->line("**Godzina:** {$appointmentTime}")
            ->line('W przypadku konieczności zmiany terminu lub odwołania wizyty, prosimy o kontakt z recepcją lub skorzystanie z opcji zarządzania wizytami w panelu pacjenta.')
            ->salutation('Z poważaniem, Zespół NovaMed')
            ->line(config('Nova Med'));
    }

    public function toArray(object $notifiable): array
    {
        $appointmentDetailUrl = rtrim(env('FRONTEND_URL', 'http://127.0.0.1:8000'), '/') . '/patient/appointments/' . $this->appointment->id;

        return [
            'appointment_id' => $this->appointment->id,
            'message' => "Twoja wizyta na zabieg {$this->appointment->procedure->name} została pomyślnie zarezerwowana.",
        ];
    }
}
