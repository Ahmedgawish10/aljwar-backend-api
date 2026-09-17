<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PopularDestinationBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'travel_date' => optional($this->travel_date)?->format('Y-m-d'),
            'return_date' => optional($this->return_date)?->format('Y-m-d'),
            'travelers' => $this->travelers,
            'status' => $this->status,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
