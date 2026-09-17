<?php

namespace App\Models\Concerns;

trait HasMediaUrl
{
    public function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            return url($path);
        }

        return url('storage/'.$path);
    }
}
