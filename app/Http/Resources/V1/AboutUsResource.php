<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AboutUsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'main_image' => $this->mediaUrl($this->main_image),
            'overline' => $this->overline,
            'title' => $this->title,
            'info' => $this->info,
            'stats' => $this->stats ?? [],
            'values' => $this->values ?? [
                'title' => null,
                'items' => [],
            ],
        ];
    }
}
