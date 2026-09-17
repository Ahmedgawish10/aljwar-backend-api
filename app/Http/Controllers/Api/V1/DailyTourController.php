<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DailyTourCategoryResource;
use App\Http\Resources\V1\DailyTourDetailResource;
use App\Http\Resources\V1\DailyTourListResource;
use App\Models\DailyTour;
use App\Models\DailyTourCategory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DailyTourController extends Controller
{
    /**
     * GET /api/v1/daily-tours
     *   slug      → one tour
     *   category  → tours in that category
     *   otherwise → category cards (landing)
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'slug' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'with_tours' => ['nullable'],
            'q' => ['nullable', 'string', 'max:100'],
            'destination' => ['nullable'],
            'tour_type' => ['nullable'],
            'duration_key' => ['nullable'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'best_seller' => ['nullable'],
            'sort' => ['nullable', 'in:recommended,priceLow,priceHigh,rating'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if (! empty($filters['slug'])) {
            return $this->showTour($filters['slug']);
        }

        if (! empty($filters['category']) || ! empty($filters['q']) || ! empty($filters['destination'])) {
            return $this->listTours($filters);
        }

        return app(DailyTourCategoryController::class)->index($request);
    }

    public function store(Request $request)
    {
        if ($request->query('slug')) {
            return $this->update($request);
        }

        $data = $this->validateTourAttributes($request->all(), isCreate: true);
        $data['external_id'] = $data['id'] ?? $data['slug'];
        unset($data['id']);

        $data = $this->applyCategory($data);
        $data['price'] = $this->normalizePrice($data['price'] ?? 0);
        $data['image'] = $this->saveImage($request->file('image'));
        $data['hero_image'] = $this->saveImage($request->file('hero_image'));
        $data['gallery'] = $this->saveGallery($request);

        $tour = DailyTour::create(array_merge($data, [
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? (int) DailyTour::query()->max('sort_order') + 1,
        ]));

        return response()->json([
            'message' => 'Daily tour created successfully',
            'data' => (new DailyTourDetailResource($tour->load('category')))->resolve(),
        ], 201, [], JSON_UNESCAPED_SLASHES);
    }

    public function update(Request $request)
    {
        $this->acceptPatchFiles($request);

        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        $tour = DailyTour::query()->where('slug', $slug)->firstOrFail();
        $data = $this->validateTourAttributes($request->except(['slug']), isCreate: false, tourId: $tour->id);

        if (isset($data['id'])) {
            $data['external_id'] = $data['id'];
            unset($data['id']);
        }

        $data = $this->applyCategory($data);

        if (array_key_exists('price', $data)) {
            $data['price'] = $this->normalizePrice($data['price'], $tour->price ?? []);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->saveImage($request->file('image'));
        } else {
            unset($data['image']);
        }

        if ($request->hasFile('hero_image')) {
            $data['hero_image'] = $this->saveImage($request->file('hero_image'));
        } else {
            unset($data['hero_image']);
        }

        if ($request->hasFile('gallery')) {
            $data['gallery'] = $this->saveGallery($request);
        } else {
            unset($data['gallery']);
        }

        $tour->update($data);

        return response()->json([
            'message' => 'Daily tour updated successfully',
            'data' => (new DailyTourDetailResource($tour->fresh()->load('category')))->resolve(),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function destroy(Request $request)
    {
        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        DailyTour::query()->where('slug', $slug)->firstOrFail()->delete();

        return response()->json([
            'message' => 'Daily tour deleted successfully',
            'slug' => $slug,
        ]);
    }

    private function showTour(string $slug)
    {
        $tour = DailyTour::query()
            ->active()
            ->with('category')
            ->where('slug', $slug)
            ->first();

        if (! $tour) {
            return response()->json([
                'message' => 'Tour not found',
            ], 404);
        }

        return response()->json([
            'data' => (new DailyTourDetailResource($tour))->resolve(),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    private function listTours(array $filters)
    {
        $category = null;

        if (! empty($filters['category'])) {
            $category = DailyTourCategory::query()
                ->listed()
                ->where('slug', $filters['category'])
                ->first();

            if (! $category) {
                return response()->json([
                    'message' => 'Category not found',
                ], 404);
            }
        }

        $tours = DailyTour::query()
            ->forList()
            ->filter($filters)
            ->sortBy($filters['sort'] ?? 'recommended')
            ->paginate((int) ($filters['per_page'] ?? 12));

        $payload = [
            'meta' => $this->paginationMeta($tours),
            'data' => DailyTourListResource::collection($tours->getCollection()),
        ];

        if ($category) {
            $payload['category'] = (new DailyTourCategoryResource($category))->resolve();
        }

        return response()->json($payload, 200, [], JSON_UNESCAPED_SLASHES);
    }

    private function applyCategory(array $data): array
    {
        $slug = $data['category'] ?? $data['tour_type'] ?? null;
        unset($data['category']);

        if (! $slug) {
            return $data;
        }

        $category = DailyTourCategory::requireBySlug($slug);

        return array_merge($data, $category->tourAttributes(), [
            'tour_type_label' => $data['tour_type_label'] ?? $category->name,
        ]);
    }

    private function validateTourAttributes(array $input, bool $isCreate, ?int $tourId = null): array
    {
        $idRule = $isCreate
            ? ['nullable', 'string', 'max:100', Rule::unique('daily_tours', 'external_id')]
            : ['sometimes', 'string', 'max:100', Rule::unique('daily_tours', 'external_id')->ignore($tourId)];

        $slugRule = $isCreate
            ? ['required', 'string', 'max:255', Rule::unique('daily_tours', 'slug')]
            : ['sometimes', 'string', 'max:255', Rule::unique('daily_tours', 'slug')->ignore($tourId)];

        return Validator::make($input, [
            'id' => $idRule,
            'slug' => $slugRule,
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'destination' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:50'],
            'destination_label' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'overview' => ['nullable', 'string'],
            'image' => ['nullable', 'file', 'image', 'max:10240'],
            'hero_image' => ['nullable', 'file', 'image', 'max:10240'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['file', 'image', 'max:10240'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'review_count' => ['nullable', 'integer', 'min:0'],
            'category' => [$isCreate ? 'required_without:tour_type' : 'sometimes', 'nullable', 'string', 'max:255'],
            'tour_type' => [$isCreate ? 'required_without:category' : 'sometimes', 'nullable', 'string', 'max:50'],
            'tour_type_label' => ['nullable', 'string', 'max:100'],
            'tour_code' => ['nullable', 'string', 'max:50'],
            'duration' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:50'],
            'duration_hours' => ['nullable', 'integer', 'min:0'],
            'duration_key' => [$isCreate ? 'required' : 'sometimes', 'string', 'in:halfDay,fullDay,multiDay'],
            'run' => ['nullable', 'string', 'max:50'],
            'group_size' => ['nullable', 'string', 'max:50'],
            'best_seller' => ['nullable', 'boolean'],
            'pickup_time' => ['nullable', 'string', 'max:50'],
            'languages' => ['nullable', 'string', 'max:255'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['string'],
            'itinerary' => ['nullable', 'array'],
            'inclusions' => ['nullable', 'array'],
            'inclusions.*' => ['string'],
            'exclusions' => ['nullable', 'array'],
            'exclusions.*' => ['string'],
            'cancellation_policy' => ['nullable', 'string'],
            'info_voucher' => ['nullable', 'string'],
            'price' => [$isCreate ? 'required' : 'sometimes'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ])->validate();
    }

    private function normalizePrice(mixed $price, array $current = []): array
    {
        if (is_numeric($price)) {
            return [
                'amount' => (float) $price,
                'currency' => $current['currency'] ?? 'USD',
            ];
        }

        $merged = array_merge($current, is_array($price) ? $price : []);

        $normalized = [
            'amount' => (float) ($merged['amount'] ?? 0),
            'currency' => $merged['currency'] ?? 'USD',
        ];

        if (isset($merged['child_amount'])) {
            $normalized['child_amount'] = (float) $merged['child_amount'];
        }

        if (isset($merged['infant_amount'])) {
            $normalized['infant_amount'] = (float) $merged['infant_amount'];
        }

        return $normalized;
    }

    private function saveGallery(Request $request): array
    {
        $files = $request->file('gallery', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        $saved = [];
        foreach ($files as $file) {
            $path = $this->saveImage($file);
            if ($path) {
                $saved[] = $path;
            }
        }

        return $saved;
    }

    private function saveImage(?UploadedFile $image): ?string
    {
        if (! $image) {
            return null;
        }

        return $image->storeAs('tours', $image->getClientOriginalName(), 'public');
    }
}
