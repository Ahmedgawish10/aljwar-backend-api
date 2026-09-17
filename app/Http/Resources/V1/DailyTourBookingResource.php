<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyTourBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'tour_date' => optional($this->tour_date)?->format('Y-m-d'),
            'adults' => (int) $this->adults,
            'children' => (int) $this->children,
            'infants' => (int) $this->infants,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'nationality' => $this->nationality,
            'adults_total' => (float) $this->adults_total,
            'children_total' => (float) $this->children_total,
            'infants_total' => (float) $this->infants_total,
            'total_price' => (float) $this->total_price,
            'currency' => $this->currency,
            'status' => $this->status,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
