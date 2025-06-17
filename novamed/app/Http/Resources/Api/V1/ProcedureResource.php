<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\ProcedureCategoryResource;

class ProcedureResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'recovery_info' => $this->recovery_timeline_info,
            'duration' => $this->duration_minutes,
            'category' => new ProcedureCategoryResource($this->whenLoaded('category')),

        ];
    }
}
