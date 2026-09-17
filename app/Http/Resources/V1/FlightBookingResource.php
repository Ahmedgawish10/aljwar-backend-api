<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'flight_id' => $this->external_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'adults' => (int) $this->adults,
            'children' => (int) $this->children,
            'nationality' => $this->nationality,
            'addons' => $this->addons ?? [],
            'passengers_fare' => (float) $this->passengers_fare,
            'addons_total' => (float) $this->addons_total,
            'total_price' => (float) $this->total_price,
            'currency' => $this->currency,
            'status' => $this->status,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
