<?php

namespace App\Models;

use App\Models\Concerns\HasMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class DailyTourCategory extends Model
{
    use HasMediaUrl;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'image',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tours(): HasMany
    {
        return $this->hasMany(DailyTour::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeListed(Builder $query): Builder
    {
        return $query
            ->active()
            ->withCount(['tours' => fn ($q) => $q->active()])
            ->orderByDesc('id');
    }

    public function scopeWithActiveTours(Builder $query): Builder
    {
        return $query->with([
            'tours' => fn ($q) => $q->active()
                ->with('category')
                ->select(DailyTour::LIST_COLUMNS)
                ->sortBy('recommended'),
        ]);
    }

    public static function requireBySlug(string $slug): self
    {
        $category = static::query()->where('slug', $slug)->first();

        if (! $category) {
            throw ValidationException::withMessages([
                'category' => 'The selected category is invalid.',
            ]);
        }

        return $category;
    }

    public function tourAttributes(): array
    {
        return [
            'daily_tour_category_id' => $this->id,
            'tour_type' => $this->slug,
            'tour_type_label' => $this->name,
        ];
    }
}
