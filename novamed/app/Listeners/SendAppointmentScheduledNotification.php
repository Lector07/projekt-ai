<?php

namespace App\Listeners;

use App\Events\AppointmentScheduled;
use App\Notifications\AppointmentBookedConfirmation; // <<< --- ZMIEŃ IMPORT
// use App\Mail\NewAppointmentNotification; // Już niepotrzebne
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
// use Illuminate\Support\Facades\Mail; // Już niepotrzebne, Notifiable sam obsłuży
use Illuminate\Support\Facades\Log;

class SendAppointmentScheduledNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AppointmentScheduled $event): void
    {
        if ($event->appointment->status === 'scheduled') {
            try {

                $patient = $event->appointment->patient;

                if ($patient && $patient->email) {
                    $patient->notify(new AppointmentBookedConfirmation($event->appointment));

                    Log::info("Wysłano notyfikację (email) o zaplanowanej wizycie #{$event->appointment->id} do {$patient->email}");
                } else {
                    Log::warning("Nie można wysłać notyfikacji dla wizyty #{$event->appointment->id} - brak pacjenta lub jego emaila.");
                }

            } catch (\Exception $e) {
                Log::error("Błąd podczas wysyłania notyfikacji o zaplanowanej wizycie #{$event->appointment->id}: " . $e->getMessage(), ['exception' => $e]);
            }
        }
    }
}
