<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'check_in' => optional($this->check_in)?->format('Y-m-d'),
            'check_out' => optional($this->check_out)?->format('Y-m-d'),
            'nights' => (int) $this->nights,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'nationality' => $this->nationality,
            'night_price' => (float) $this->night_price,
            'total_price' => (float) $this->total_price,
            'currency' => $this->currency,
            'status' => $this->status,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
