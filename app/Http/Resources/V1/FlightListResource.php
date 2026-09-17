<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->external_id,
            'airline' => $this->airline,
            'airline_name' => $this->airline_name,
            'airline_logo' => $this->mediaUrl($this->airline_logo),
            'depart_time' => $this->depart_time,
            'arrive_time' => $this->arrive_time,
            'duration' => $this->duration,
            'stops' => $this->stops,
            'departure' => $this->departure,
            'arrival' => $this->arrival,
            'departure_slot' => $this->departure_slot,
            'image' => $this->mediaUrl($this->image),
            'price' => $this->pricePayload(),
        ];
    }
}
