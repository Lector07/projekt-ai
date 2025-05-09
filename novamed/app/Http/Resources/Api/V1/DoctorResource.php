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
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'specialization' => $this->specialization,
            'bio' => $this->bio,
            'price_modifier' => $this->price_modifier,
            'avatar_url' => $this->profile_picture_path ? Storage::disk('public')->url($this->profile_picture_path) : null,
        ];
    }
}
