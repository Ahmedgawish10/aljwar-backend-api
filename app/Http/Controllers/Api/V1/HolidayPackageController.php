<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\HolidayPackageDetailResource;
use App\Http\Resources\V1\HolidayPackageListResource;
use App\Models\HolidayPackage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HolidayPackageController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'slug' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'destination' => ['nullable', 'string', 'max:50'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:recommended,priceLow,priceHigh'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if (! empty($filters['slug'])) {
            $package = HolidayPackage::query()
                ->active()
                ->where('slug', $filters['slug'])
                ->firstOrFail();

            return response()->json([
                'data' => (new HolidayPackageDetailResource($package))->resolve(),
            ], 200, [], JSON_UNESCAPED_SLASHES);
        }

        $perPage = (int) ($filters['per_page'] ?? 12);

        $packages = HolidayPackage::query()
            ->active()
            ->select(HolidayPackage::LIST_COLUMNS)
            ->filter($filters)
            ->sortBy($filters['sort'] ?? 'recommended')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($packages),
            'data' => HolidayPackageListResource::collection($packages->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function store(Request $request)
    {
        $input = $request->input('attributes', $request->all());
        $validated = $this->validateAttributes($input, isCreate: true);

        $validated['external_id'] = $validated['id'] ?? $validated['slug'];
        unset($validated['id']);

        [$amount, $currency] = $this->normalizePrice($validated['price'] ?? $validated['price_from'] ?? 0);
        $validated['price_from'] = $amount;
        $validated['currency'] = $currency;
        unset($validated['price']);

        $validated['image'] = $this->saveImage($request->file('image'));
        $validated['gallery'] = $this->saveGallery($request, $validated['gallery'] ?? []);

        $package = HolidayPackage::create(array_merge($validated, [
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? (int) HolidayPackage::query()->max('sort_order') + 1,
        ]));

        return response()->json([
            'message' => 'Holiday package created successfully',
            'data' => (new HolidayPackageDetailResource($package))->resolve(),
        ], 201, [], JSON_UNESCAPED_SLASHES);
    }

    public function update(Request $request)
    {
        $this->acceptPatchFiles($request);

        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        $package = HolidayPackage::query()->where('slug', $slug)->firstOrFail();
        $input = $request->input('attributes', $request->except(['slug']));
        $validated = $this->validateAttributes($input, isCreate: false, packageId: $package->id);

        if (isset($validated['id'])) {
            $validated['external_id'] = $validated['id'];
            unset($validated['id']);
        }

        if (array_key_exists('price', $validated) || array_key_exists('price_from', $validated)) {
            [$amount, $currency] = $this->normalizePrice(
                $validated['price'] ?? $validated['price_from'],
                $package->currency ?? 'USD'
            );
            $validated['price_from'] = $amount;
            $validated['currency'] = $currency;
            unset($validated['price']);
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $this->saveImage($request->file('image'));
        }

        if ($request->hasFile('gallery') || array_key_exists('gallery', $validated)) {
            $validated['gallery'] = $this->saveGallery($request, $validated['gallery'] ?? []);
        }

        $package->update($validated);

        return response()->json([
            'message' => 'Holiday package updated successfully',
            'data' => (new HolidayPackageDetailResource($package->fresh()))->resolve(),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function destroy(Request $request)
    {
        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        $package = HolidayPackage::query()->where('slug', $slug)->firstOrFail();
        $package->delete();

        return response()->json([
            'message' => 'Holiday package deleted successfully',
            'slug' => $slug,
        ]);
    }

    private function validateAttributes(array $input, bool $isCreate, ?int $packageId = null): array
    {
        $idRule = $isCreate
            ? ['nullable', 'string', 'max:100', Rule::unique('holiday_packages', 'external_id')]
            : ['sometimes', 'string', 'max:100', Rule::unique('holiday_packages', 'external_id')->ignore($packageId)];

        $slugRule = $isCreate
            ? ['required', 'string', 'max:255', Rule::unique('holiday_packages', 'slug')]
            : ['sometimes', 'string', 'max:255', Rule::unique('holiday_packages', 'slug')->ignore($packageId)];

        return Validator::make($input, [
            'id' => $idRule,
            'slug' => $slugRule,
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:50'],
            'duration' => ['nullable', 'string', 'max:100'],
            'days' => ['nullable', 'integer', 'min:0'],
            'nights' => ['nullable', 'integer', 'min:0'],
            'price' => [$isCreate ? 'required' : 'sometimes'],
            'price_from' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'reviews' => ['nullable', 'integer', 'min:0'],
            'tour_type' => ['nullable', 'string', 'max:100'],
            'group_size' => ['nullable', 'string', 'max:100'],
            'badge' => ['nullable', 'string', 'max:50'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
            'image' => ['nullable'],
            'gallery' => [$isCreate ? 'nullable' : 'sometimes', 'nullable', 'array'],
            'gallery.*' => ['nullable'],
            'overview' => ['nullable', 'string'],
            'inclusions_bar' => ['nullable', 'array'],
            'itinerary' => ['nullable', 'array'],
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

    private function saveGallery(Request $request, array $current = []): array
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

        foreach ($current as $item) {
            if ($item instanceof UploadedFile) {
                continue;
            }

            if (is_string($item) && $item !== '') {
                $saved[] = $this->saveImage($item) ?? $item;
            }
        }

        return $saved;
    }

    private function saveImage(mixed $image): mixed
    {
        if ($image instanceof UploadedFile) {
            return $image->storeAs('holiday-packages', $image->getClientOriginalName(), 'public');
        }

        if (! is_string($image) || ! str_starts_with($image, 'data:image')) {
            return $image;
        }

        $ext = str_contains($image, 'png') ? 'png' : (str_contains($image, 'webp') ? 'webp' : 'jpg');
        $name = uniqid().'.'.$ext;
        Storage::disk('public')->put('holiday-packages/'.$name, base64_decode(explode(',', $image, 2)[1] ?? ''));

        return 'holiday-packages/'.$name;
    }
}
