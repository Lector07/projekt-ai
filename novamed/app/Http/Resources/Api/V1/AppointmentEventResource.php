<?php
// app/Http/Resources/Api/V1/AppointmentEventResource.php
namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AppointmentEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $startDateTime = Carbon::parse($this->appointment_datetime);
        $procedureDuration = $this->whenLoaded('procedure', fn() => $this->procedure->duration_minutes ?? 60, 60);
        $endDateTime = $startDateTime->copy()->addMinutes($procedureDuration);

        return [
            'id' => $this->id,
            'title' => ($this->whenLoaded('patient') ? $this->patient->name : 'Pacjent') . ' - ' . ($this->whenLoaded('procedure') ? $this->procedure->name : 'Zabieg'),
            'start' => $startDateTime->toIso8601String(),
            'end' => $endDateTime->toIso8601String(),
            'allDay' => false,
            'extendedProps' => [
                'appointment_id' => $this->id,
                'status' => $this->status,
                'patient_name' => $this->whenLoaded('patient', fn() => $this->patient->name),
                'procedure_name' => $this->whenLoaded('procedure', fn() => $this->procedure->name),
            ],
        ];
    }
}
