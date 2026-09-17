<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'location' => $this->location,
            'destination' => $this->destination,
            'description' => $this->descriptionText(),
            'main_image' => $this->mediaUrl($this->main_image),
            'gallery' => collect($this->gallery ?? [])
                ->map(fn ($path) => $this->mediaUrl($path))
                ->values()
                ->all(),
            'stars' => (int) $this->stars,
            'rating' => (float) $this->rating,
            'review_label_key' => $this->review_label_key,
            'review_count' => (int) $this->review_count,
            'review_breakdown' => $this->review_breakdown ?? [],
            'property_type' => $this->property_type,
            'best_seller' => (bool) $this->best_seller,
            'amenities' => $this->amenities ?? [],
            'price' => $this->pricePayload(),
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'pets' => $this->pets,
            'things_to_remember' => $this->things_to_remember ?? [],
        ];
    }
}
