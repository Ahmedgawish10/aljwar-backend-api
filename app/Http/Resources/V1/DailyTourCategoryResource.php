<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyTourCategoryResource extends JsonResource
{
    public static function brief(?object $category, bool $detailed = false): ?array
    {
        if (! $category) {
            return null;
        }

        $data = [
            'slug' => $category->slug,
            'name' => $category->name,
        ];

        if ($detailed) {
            $data['description'] = $category->description;
        }

        return $data;
    }

    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->mediaUrl($this->image),
            'tours_count' => (int) ($this->tours_count ?? 0),
            'tours' => $this->when(
                $this->relationLoaded('tours'),
                fn () => DailyTourListResource::collection($this->tours)
            ),
        ];
    }
}
