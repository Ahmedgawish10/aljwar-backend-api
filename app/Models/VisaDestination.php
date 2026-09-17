<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaDestination extends Model
{
    protected $table = 'visas';

    protected $fillable = [
        'country_name',
        'country_image',
        'country_flag',
        'price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(VisaApplication::class, 'visa_id');
    }

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

    public function pricePayload(): array
    {
        return [
            'amount' => (int) $this->price,
            'currency' => 'USD',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.trim($term).'%';
                $q->where('country_name', 'like', $like);
            })
            ->when($filters['min_price'] ?? null, fn ($q, $v) => $q->where('price', '>=', (float) $v))
            ->when($filters['max_price'] ?? null, fn ($q, $v) => $q->where('price', '<=', (float) $v));
    }
}
