<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'contactMessage',
            'id' => $this->id,
            'attributes' => [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'name' => trim($this->first_name . ' ' . $this->last_name),
                'email' => $this->email,
                'phone' => $this->phone,
                'country' => $this->country,
                'subject' => $this->subject,
                'service' => $this->service,
                'message' => $this->message,
                'privacy' => (bool) $this->privacy,
                'status' => $this->status,
                'locale' => $this->locale,
                'created_at' => optional($this->created_at)?->toIso8601String(),
                'updated_at' => optional($this->updated_at)?->toIso8601String(),
            ],
        ];
    }
}
