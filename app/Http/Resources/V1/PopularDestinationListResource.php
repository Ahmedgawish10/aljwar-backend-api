<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PopularDestinationListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'country' => $this->country,
            'categories' => $this->categories ?? [],
            'featured' => (bool) $this->featured,
            'image' => $this->mediaUrl($this->image),
            'price' => $this->pricePayload(),
        ];
    }
}
