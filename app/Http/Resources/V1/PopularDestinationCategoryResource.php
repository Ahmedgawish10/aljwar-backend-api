<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PopularDestinationCategoryResource extends JsonResource
{
    public static function brief(?object $category): ?array
    {
        if (! $category) {
            return null;
        }

        return [
            'slug' => $category->slug,
            'name' => $category->name,
        ];
    }

    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->mediaUrl($this->image),
            'is_active' => (bool) $this->is_active,
            'destinations_count' => (int) ($this->destinations_count ?? 0),
            'price' => [
                'amount' => (int) ($this->price_from ?? 0),
                'currency' => $this->currency ?? 'USD',
            ],
        ];
    }
}
