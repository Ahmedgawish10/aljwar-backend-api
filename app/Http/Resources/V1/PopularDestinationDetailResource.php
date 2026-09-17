<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PopularDestinationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $info = $this->info ?? [];

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'country' => $this->country,
            'categories' => $this->categories ?? [],
            'featured' => (bool) $this->featured,
            'image' => $this->mediaUrl($this->image),
            'gallery' => collect($this->gallery ?? [])
                ->map(fn ($path) => $this->mediaUrl($path))
                ->values()
                ->all(),
            'overview' => $this->overview,
            'highlights' => $this->highlights ?? [],
            'info' => [
                'language' => $info['language'] ?? null,
                'currency' => $info['currency'] ?? null,
                'timezone' => $info['timezone'] ?? null,
                'best_time' => $info['best_time'] ?? null,
            ],
            'price' => $this->pricePayload(),
        ];
    }
}
