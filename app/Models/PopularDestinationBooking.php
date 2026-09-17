<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PopularDestinationBooking extends Model
{
    protected $table = 'popular_destination_bookings';

    protected $fillable = [
        'popular_destination_id',
        'slug',
        'full_name',
        'email',
        'phone',
        'travel_date',
        'return_date',
        'travelers',
        'status',
    ];

    protected $casts = [
        'travel_date' => 'date',
        'return_date' => 'date',
    ];

    public function popularDestination(): BelongsTo
    {
        return $this->belongsTo(PopularDestination::class);
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
