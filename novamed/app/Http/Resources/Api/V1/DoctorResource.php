<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'specialization' => $this->specialization,
            'bio' => $this->bio,
            'price_modifier' => $this->price_modifier,
            'profile_picture_url' => $this->profile_picture_path
                ? Storage::disk('public')->url($this->profile_picture_path)
                : null,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'procedure_ids' => $this->whenLoaded('procedures', function() {
                return $this->procedures->pluck('id');
            }),
            'updated_at' => $this->updated_at,
        ];
    }
}
