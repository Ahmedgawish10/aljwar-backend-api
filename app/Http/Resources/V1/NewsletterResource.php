<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsletterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'newsletter',
            'id' => $this->id,
            'attributes' => [
                'email' => $this->email,
                'status' => $this->status,
                'created_at' => optional($this->created_at)?->toIso8601String(),
                'updated_at' => optional($this->updated_at)?->toIso8601String(),
            ],
        ];
    }
}
