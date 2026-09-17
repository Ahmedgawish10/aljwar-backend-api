<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PopularDestinationCategoryResource;
use App\Http\Resources\V1\PopularDestinationDetailResource;
use App\Http\Resources\V1\PopularDestinationListResource;
use App\Models\PopularDestination;
use App\Models\PopularDestinationCategory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PopularDestinationController extends Controller
{
    /**
     * GET /api/v1/popular-destinations
     *   slug      → one destination
     *   category  → destinations in that category
     *   otherwise → category cards (landing)
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'slug' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'],
            'featured' => ['nullable'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:recommended,priceLow,priceHigh,name'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if (! empty($filters['slug'])) {
            return $this->showDestination($filters['slug']);
        }

        if (
            ! empty($filters['category'])
            || ! empty($filters['q'])
            || ! empty($filters['featured'])
            || isset($filters['min_price'])
            || isset($filters['max_price'])
        ) {
            return $this->listDestinations($filters);
        }

        return app(PopularDestinationCategoryController::class)->index($request);
    }

    public function store(Request $request)
    {
        if ($request->query('slug')) {
            return $this->update($request);
        }

        $validated = $this->validateAttributes($request->all(), isCreate: true);

        $validated['external_id'] = $validated['id'] ?? $validated['slug'];
        unset($validated['id']);

        [$amount, $currency] = $this->normalizePrice($validated['price'] ?? $validated['price_from'] ?? 0);
        $validated['price_from'] = $amount;
        $validated['currency'] = $currency;
        unset($validated['price']);

        $validated['image'] = $this->saveImage($request->file('image'));
        $validated['gallery'] = $this->saveGallery($request);

        $item = PopularDestination::create(array_merge($validated, [
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? (int) PopularDestination::query()->max('sort_order') + 1,
        ]));

        return response()->json([
            'message' => 'Popular destination created successfully',
            'data' => (new PopularDestinationDetailResource($item))->resolve(),
        ], 201);
    }

    public function update(Request $request)
    {
        $this->acceptPatchFiles($request);

        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        $item = PopularDestination::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $validated = $this->validateAttributes($request->except(['slug']), isCreate: false, id: $item->id);

        if (isset($validated['id'])) {
            $validated['external_id'] = $validated['id'];
            unset($validated['id']);
        }

        if (array_key_exists('price', $validated) || array_key_exists('price_from', $validated)) {
            [$amount, $currency] = $this->normalizePrice(
                $validated['price'] ?? $validated['price_from'],
                $item->currency ?? 'USD'
            );
            $validated['price_from'] = $amount;
            $validated['currency'] = $currency;
            unset($validated['price']);
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $this->saveImage($request->file('image'));
        } else {
            unset($validated['image']);
        }

        if ($request->hasFile('gallery')) {
            $validated['gallery'] = $this->saveGallery($request);
        } else {
            unset($validated['gallery']);
        }

        $item->update($validated);

        return response()->json([
            'message' => 'Popular destination updated successfully',
            'data' => (new PopularDestinationDetailResource($item->fresh()))->resolve(),
        ]);
    }

    public function destroy(Request $request)
    {
        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        $item = PopularDestination::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $item->delete();

        return response()->json([
            'message' => 'Popular destination deleted successfully',
            'slug' => $slug,
        ]);
    }

    private function validateAttributes(array $input, bool $isCreate, ?int $id = null): array
    {
        $idRule = $isCreate
            ? ['nullable', 'string', 'max:100', Rule::unique('popular_destinations', 'external_id')]
            : ['sometimes', 'string', 'max:100', Rule::unique('popular_destinations', 'external_id')->ignore($id)];

        $slugRule = $isCreate
            ? ['required', 'string', 'max:255', Rule::unique('popular_destinations', 'slug')]
            : ['sometimes', 'string', 'max:255', Rule::unique('popular_destinations', 'slug')->ignore($id)];

        return Validator::make($input, [
            'id' => $idRule,
            'slug' => $slugRule,
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', Rule::in(['beach', 'city', 'adventure', 'nature', 'culture'])],
            'price' => ['sometimes'],
            'price_from' => ['nullable', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'featured' => ['nullable', 'boolean'],
            'image' => ['nullable'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['file', 'image', 'max:10240'],
            'overview' => ['nullable', 'string'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['string'],
            'info' => ['nullable', 'array'],
            'info.language' => ['nullable', 'string', 'max:150'],
            'info.currency' => ['nullable', 'string', 'max:100'],
            'info.timezone' => ['nullable', 'string', 'max:50'],
            'info.best_time' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ])->validate();
    }

    private function normalizePrice(mixed $price, string $fallbackCurrency = 'USD'): array
    {
        if (is_array($price)) {
            return [
                (int) ($price['amount'] ?? 0),
                $price['currency'] ?? $fallbackCurrency,
            ];
        }

        return [(int) $price, $fallbackCurrency];
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

        return $image->storeAs('destinations', time().'_'.$image->getClientOriginalName(), 'public');
    }

    private function showDestination(string $slug)
    {
        $item = PopularDestination::query()
            ->active()
            ->where('slug', $slug)
            ->first();

        if (! $item) {
            return response()->json([
                'message' => 'Destination not found',
            ], 404);
        }

        return response()->json([
            'data' => (new PopularDestinationDetailResource($item))->resolve(),
        ]);
    }

    private function listDestinations(array $filters)
    {
        $category = null;

        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $category = PopularDestinationCategory::query()
                ->listed()
                ->where('slug', $filters['category'])
                ->first();

            if (! $category) {
                return response()->json([
                    'message' => 'Category not found',
                ], 404);
            }

            PopularDestinationCategory::attachDestinationCounts(collect([$category]));
        }

        $items = PopularDestination::query()
            ->forList()
            ->filter($filters)
            ->sortBy($filters['sort'] ?? 'recommended')
            ->paginate((int) ($filters['per_page'] ?? 12));

        $payload = [
            'meta' => $this->paginationMeta($items),
            'data' => PopularDestinationListResource::collection($items->getCollection()),
        ];

        if ($category) {
            $payload['category'] = (new PopularDestinationCategoryResource($category))->resolve();
        }

        return response()->json($payload, 200, [], JSON_UNESCAPED_SLASHES);
    }
}
