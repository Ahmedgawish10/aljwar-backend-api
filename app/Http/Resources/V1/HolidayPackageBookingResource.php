<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HolidayPackageBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'travelers' => $this->travelers,
            'departure' => optional($this->departure)?->format('Y-m-d'),
            'return_date' => optional($this->return_date)?->format('Y-m-d'),
            'price' => [
                'amount' => (int) $this->price_from,
                'currency' => $this->currency ?? 'USD',
            ],
            'total_price' => (int) $this->total_price,
            'status' => $this->status,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
