<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->external_id,
            'airline' => $this->airline,
            'airline_name' => $this->airline_name,
            'airline_logo' => $this->mediaUrl($this->airline_logo),
            'image' => $this->mediaUrl($this->image),
            'image_alt' => $this->image_alt,
            'hero_bg' => $this->hero_bg,
            'depart_time' => $this->depart_time,
            'arrive_time' => $this->arrive_time,
            'depart_date' => $this->depart_date,
            'arrive_date' => $this->arrive_date,
            'date_label' => $this->date_label,
            'duration' => $this->duration,
            'stops' => $this->stops,
            'departure' => $this->departure,
            'arrival' => $this->arrival,
            'depart_terminal' => $this->depart_terminal,
            'arrive_terminal' => $this->arrive_terminal,
            'cabin' => $this->cabin,
            'departure_slot' => $this->departure_slot,
            'price' => $this->pricePayload(),
        ];

        if ($this->arrive_next_day) {
            $data['arrive_next_day'] = true;
        }

        return $data;
    }
}
