<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\DailyTourCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyTourDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'destination' => $this->destination,
            'category' => $this->when(
                $this->relationLoaded('category'),
                fn () => DailyTourCategoryResource::brief($this->category, detailed: true)
            ),
            'tour_type' => $this->tour_type,
            'tour_code' => $this->tour_code,
            'duration' => $this->duration,
            'duration_hours' => (int) $this->duration_hours,
            'duration_key' => $this->duration_key,
            'run' => $this->run,
            'pickup_time' => $this->pickup_time,
            'languages' => $this->languages,
            'group_size' => $this->group_size,
            'best_seller' => (bool) $this->best_seller,
            'rating' => (float) $this->rating,
            'review_count' => (int) $this->review_count,
            'image' => $this->mediaUrl($this->image),
            'hero_image' => $this->mediaUrl($this->hero_image),
            'gallery' => collect($this->gallery ?? [])
                ->map(fn ($path) => $this->mediaUrl($path))
                ->values()
                ->all(),
            'overview' => $this->overview,
            'highlights' => $this->highlights ?? [],
            'itinerary' => $this->itinerary ?? [],
            'inclusions' => $this->inclusions ?? [],
            'exclusions' => $this->exclusions ?? [],
            'cancellation_policy' => $this->cancellation_policy,
            'info_voucher' => $this->info_voucher,
            'price' => $this->pricePayload(),
        ];
    }
}
