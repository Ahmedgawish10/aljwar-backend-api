<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'destination' => $this->destination,
            'duration_minutes' => (int) $this->duration_minutes,
            'experience' => $this->experience,
            'main_image' => $this->mediaUrl($this->main_image),
            'gallery' => collect($this->gallery ?? [])
                ->map(fn ($path) => $this->mediaUrl($path))
                ->values()
                ->all(),
            'inclusions' => $this->inclusions ?? [],
            'exclusions' => $this->exclusions ?? [],
            'meeting_point' => $this->meeting_point,
            'things_to_remember' => $this->things_to_remember ?? [],
            'price' => $this->pricePayload(),
        ];
    }
}
