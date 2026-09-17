<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookNowRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge([
            'id' => $this->id,
            'tab' => $this->resource::TAB,
        ], $this->payload(), [
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ]);
    }
}
