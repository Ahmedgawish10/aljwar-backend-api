<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayPackageBooking extends Model
{
    protected $fillable = [
        'holiday_package_id',
        'slug',
        'full_name',
        'email',
        'phone',
        'travelers',
        'departure',
        'return_date',
        'price_from',
        'total_price',
        'currency',
        'status',
    ];

    protected $casts = [
        'departure' => 'date',
        'return_date' => 'date',
        'price_from' => 'integer',
        'total_price' => 'integer',
    ];

    public function holidayPackage(): BelongsTo
    {
        return $this->belongsTo(HolidayPackage::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%' . trim($term) . '%';
                $q->where(function (Builder $inner) use ($like) {
                    $inner->where('slug', 'like', $like)
                        ->orWhere('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->when($filters['slug'] ?? null, fn ($q, $v) => $q->where('slug', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['email'] ?? null, fn ($q, $v) => $q->where('email', $v));
    }
}
