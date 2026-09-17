<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HolidayPackageDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'location' => $this->location,
            'destination' => $this->destination,
            'duration' => $this->duration,
            'days' => (int) $this->days,
            'nights' => (int) $this->nights,
            'rating' => (float) $this->rating,
            'reviews' => (int) $this->reviews,
            'tour_type' => $this->tour_type,
            'group_size' => $this->group_size,
            'badge' => $this->badge,
            'categories' => $this->categories ?? [],
            'features' => $this->features ?? [],
            'image' => $this->mediaUrl($this->image),
            'gallery' => collect($this->gallery ?? [])
                ->map(fn ($path) => $this->mediaUrl($path))
                ->values()
                ->all(),
            'overview' => $this->overview,
            'inclusions_bar' => $this->inclusions_bar ?? [],
            'itinerary' => $this->itinerary ?? [],
            'price' => $this->pricePayload(),
        ];
    }
}
