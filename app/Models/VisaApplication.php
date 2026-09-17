<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VisaApplication extends Model
{
    protected $fillable = [
        'visa_id',
        'country',
        'visa_type',
        'purpose',
        'nationality',
        'arrival_date',
        'applicants',
        'full_name',
        'email',
        'phone',
        'passport_number',
        'date_of_birth',
        'passport_file',
        'photo_file',
        'additional_file',
        'unit_price',
        'total_price',
        'currency',
        'status',
    ];

    protected $casts = [
        'arrival_date' => 'date:Y-m-d',
        'date_of_birth' => 'date:Y-m-d',
        'applicants' => 'integer',
        'unit_price' => 'float',
        'total_price' => 'float',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(VisaDestination::class, 'visa_id');
    }

    public function fileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return url('storage/'.$path);
    }

    protected static function booted(): void
    {
        static::deleting(function (VisaApplication $application) {
            foreach (['passport_file', 'photo_file', 'additional_file'] as $column) {
                if ($application->{$column}) {
                    Storage::disk('public')->delete($application->{$column});
                }
            }
        });
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
                        ->orWhere('passport_number', 'like', $like)
                        ->orWhere('country', 'like', $like);
                });
            })
            ->when($filters['country'] ?? null, fn ($q, $v) => $q->where('country', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['visa_type'] ?? null, fn ($q, $v) => $q->where('visa_type', $v))
            ->when($filters['email'] ?? null, fn ($q, $v) => $q->where('email', $v));
    }
}
