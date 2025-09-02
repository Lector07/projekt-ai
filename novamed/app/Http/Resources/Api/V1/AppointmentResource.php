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
            'status' => $this->status,
            'patient_notes' => $this->patient_notes,
            'admin_notes' => $this->admin_notes,
            'patient' => $this->whenLoaded('patient', function() {
                return [
                    'id' => $this->patient->id,
                    'name' => $this->patient->name,
                    'email' => $this->patient->email
                ];
            }),
            'doctor' => $this->whenLoaded('doctor', function() {
                return [
                    'id' => $this->doctor->id,
                    'first_name' => $this->doctor->first_name,
                    'last_name' => $this->doctor->last_name,
                    'specialization' => $this->doctor->specialization,
                ];
            }),
            'procedure' => $this->whenLoaded('procedure', function() {
                return [
                    'id' => $this->procedure->id,
                    'name' => $this->procedure->name,
                    'description' => $this->procedure->description,
                    'base_price' => $this->procedure->base_price,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function translateStatus(string $status): string
    {
        switch ($status) {
            case 'scheduled': return 'Zaplanowana';
            case 'confirmed': return 'Potwierdzona';
            case 'completed': return 'Zakończona';
            case 'cancelled_by_patient': return 'Odwołana przez pacjenta';
            case 'cancelled_by_clinic': return 'Odwołana przez klinikę';
            case 'no_show': return 'Nieobecność';
            default: return ucfirst($status);
        }
    }
}
