<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_datetime' => $this->appointment_datetime,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,
            'notes' => $this->notes,
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
                    'name' => $this->doctor->name,
                    'email' => $this->doctor->email
                ];
            }),
            'procedure' => $this->whenLoaded('procedure', function() {
                return [
                    'id' => $this->procedure->id,
                    'name' => $this->procedure->name,
                    'price' => $this->procedure->price
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
