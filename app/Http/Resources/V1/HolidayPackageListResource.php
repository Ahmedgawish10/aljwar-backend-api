<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HolidayPackageListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'location' => $this->location,
            'destination' => $this->destination,
            'duration' => $this->duration,
            'badge' => $this->badge,
            'categories' => $this->categories ?? [],
            'features' => $this->features ?? [],
            'image' => $this->mediaUrl($this->image),
            'itinerary' => $this->itinerary ?? [],
            'price' => $this->pricePayload(),
        ];
    }
}
