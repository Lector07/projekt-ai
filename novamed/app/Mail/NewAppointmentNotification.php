<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address; // Dodaj ten import

class NewAppointmentNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Appointment $appointment;

    /**
     * Create a new message instance.
     *
     * @param \App\Models\Appointment $appointment
     * @return void
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $patientEmail = $this->appointment->patient->email;
        $patientName = $this->appointment->patient->name;

        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'noreply@example.com'), env('MAIL_FROM_NAME', 'NovaMed Klinika')),
            to: [new Address($patientEmail, $patientName)],
            subject: 'Potwierdzenie rezerwacji wizyty w NovaMed',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointments.scheduled',
            with: [
                'appointmentId' => $this->appointment->id,
                'patientName' => $this->appointment->patient->name,
                'doctorName' => $this->appointment->doctor->first_name . ' ' . $this->appointment->doctor->last_name,
                'procedureName' => $this->appointment->procedure->name,
                'appointmentDate' => \Carbon\Carbon::parse($this->appointment->appointment_datetime)->format('d.m.Y'),
                'appointmentTime' => \Carbon\Carbon::parse($this->appointment->appointment_datetime)->format('H:i'),
                'clinicName' => env('APP_NAME', 'NovaMed Klinika'),
                'myAppointmentsUrl' => url('/patient/appointments'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
