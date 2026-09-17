<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelBooking extends Model
{
    protected $fillable = [
        'hotel_id',
        'slug',
        'check_in',
        'check_out',
        'nights',
        'full_name',
        'email',
        'phone',
        'nationality',
        'night_price',
        'total_price',
        'currency',
        'status',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'nights' => 'integer',
        'night_price' => 'float',
        'total_price' => 'float',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%' . trim($term) . '%';
                $q->where(function (Builder $inner) use ($like) {
                    $inner->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('slug', 'like', $like);
                });
            })
            ->when($filters['slug'] ?? null, fn ($q, $v) => $q->where('slug', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['email'] ?? null, fn ($q, $v) => $q->where('email', $v));
    }
}
