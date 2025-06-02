<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\Log; // Możesz usunąć logi, jeśli już nie są potrzebne

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Log::info("--- UserResource (app/Http/Resources/Api/V1/UserResource.php) toArray CALLED for User ID: {$this->id} ---");
        $profilePictureUrl = $this->profile_picture_path
            ? Storage::disk('public')->url($this->profile_picture_path)
            : null;
        // Log::info("Generated URL in UserResource: " . $profilePictureUrl);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'avatar' => $profilePictureUrl, // LUB 'profile_picture_url', jeśli tak masz w typach TS i Vue
            'role' => $this->role, // Model User ma pole 'role'
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
