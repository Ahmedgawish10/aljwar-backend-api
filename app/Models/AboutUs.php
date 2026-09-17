<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutUs extends Model
{
    protected $table = 'about_us';

    protected $fillable = [
        'main_image',
        'overline',
        'title',
        'info',
        'stats',
        'values',
        'is_active',
    ];

    protected $casts = [
        'stats' => 'array',
        'values' => 'array',
        'is_active' => 'boolean',
    ];

    public function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return url('storage/'.$path);
    }
}
