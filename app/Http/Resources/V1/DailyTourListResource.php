<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\DailyTourCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyTourListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'destination' => $this->destination,
            'description' => $this->description,
            'image' => $this->mediaUrl($this->image),
            'rating' => (float) $this->rating,
            'review_count' => (int) $this->review_count,
            'category' => $this->when(
                $this->relationLoaded('category'),
                fn () => DailyTourCategoryResource::brief($this->category)
            ),
            'tour_type' => $this->tour_type,
            'duration' => $this->duration,
            'duration_hours' => (int) $this->duration_hours,
            'duration_key' => $this->duration_key,
            'run' => $this->run,
            'group_size' => $this->group_size,
            'best_seller' => (bool) $this->best_seller,
            'price' => $this->pricePayload(),
            'highlights' => $this->highlights ?? [],
            'itinerary' => $this->itinerary ?? [],
            'inclusions' => $this->inclusions ?? [],
            'exclusions' => $this->exclusions ?? [],
        ];
    }
}
