<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_datetime' => $this->appointment_datetime,
            // Usunięto start_time i end_time, jeśli nie są używane w tym kontekście lub są częścią appointment_datetime
            'status' => $this->status,
            'patient_notes' => $this->patient_notes, // Jeśli masz takie pole w modelu Appointment
            'admin_notes' => $this->admin_notes,   // Jeśli masz takie pole i pacjent ma je widzieć
            // 'notes' => $this->notes, // Jeśli 'notes' to jedyne pole notatek, zdecyduj które to są

            'patient' => $this->whenLoaded('patient', function() {
                return [
                    'id' => $this->patient->id,
                    'name' => $this->patient->name,
                    'email' => $this->patient->email // Możesz usunąć email, jeśli nie jest potrzebny na tej stronie
                ];
            }),
            'doctor' => $this->whenLoaded('doctor', function() {
                return [
                    'id' => $this->doctor->id,
                    // Załóżmy, że model Doctor ma first_name i last_name
                    'first_name' => $this->doctor->first_name,
                    'last_name' => $this->doctor->last_name,
                    // 'name' => $this->doctor->first_name . ' ' . $this->doctor->last_name, // Alternatywnie, jeśli frontend oczekuje 'name'
                    'specialization' => $this->doctor->specialization,
                ];
            }),
            'procedure' => $this->whenLoaded('procedure', function() {
                return [
                    'id' => $this->procedure->id,
                    'name' => $this->procedure->name,
                    'description' => $this->procedure->description, // <<< DODAJ OPIS
                    'base_price' => $this->procedure->base_price, // <<< UŻYJ base_price LUB price, ale spójnie
                    // 'price' => $this->procedure->price,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
