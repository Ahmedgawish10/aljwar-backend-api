<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisaDestinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'country_name' => $this->country_name,
            'country_image' => $this->mediaUrl($this->country_image),
            'country_flag' => $this->mediaUrl($this->country_flag),
            'price' => $this->pricePayload(),
        ];
    }
}
