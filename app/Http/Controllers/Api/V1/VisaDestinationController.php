<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\VisaDestinationResource;
use App\Models\VisaDestination;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class VisaDestinationController extends Controller
{
    /**
     * GET /api/v1/visa-services
     * GET /api/v1/visa-services?country_name=Turkey
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'country_name' => ['nullable', 'string', 'max:80'],
            'q' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! empty($filters['country_name'])) {
            $destination = VisaDestination::query()
                ->active()
                ->where('country_name', $filters['country_name'])
                ->firstOrFail();

            return response()->json([
                'data' => (new VisaDestinationResource($destination))->resolve(),
            ], 200, [], JSON_UNESCAPED_SLASHES);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        $destinations = VisaDestination::query()
            ->active()
            ->filter($filters)
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($destinations),
            'data' => VisaDestinationResource::collection($destinations->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * POST /api/v1/visa-services
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_name' => ['required', 'string', 'max:80', Rule::unique('visas', 'country_name')],
            'country_image' => ['nullable', 'file', 'image', 'max:10240'],
            'country_flag' => ['nullable', 'file', 'image', 'max:10240'],
            'price' => ['required'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $price = $this->normalizePrice($validated['price']);

        $destination = VisaDestination::create([
            'country_name' => $validated['country_name'],
            'country_image' => $this->saveImage($request->file('country_image')),
            'country_flag' => $this->saveImage($request->file('country_flag')),
            'price' => $price,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Visa destination created successfully',
            'data' => (new VisaDestinationResource($destination))->resolve(),
        ], 201, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * PATCH /api/v1/visa-services?country_name=Turkey
     */
    public function update(Request $request)
    {
        $countryName = $request->validate([
            'country_name' => ['required', 'string', 'max:80'],
        ])['country_name'];

        $destination = VisaDestination::query()
            ->where('country_name', $countryName)
            ->firstOrFail();

        $validated = $request->validate([
            'country_image' => ['sometimes', 'nullable', 'file', 'image', 'max:10240'],
            'country_flag' => ['sometimes', 'nullable', 'file', 'image', 'max:10240'],
            'price' => ['sometimes'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        if (array_key_exists('price', $validated)) {
            $validated['price'] = $this->normalizePrice($validated['price']);
        }

        if ($request->hasFile('country_image')) {
            $validated['country_image'] = $this->saveImage($request->file('country_image'));
        } else {
            unset($validated['country_image']);
        }

        if ($request->hasFile('country_flag')) {
            $validated['country_flag'] = $this->saveImage($request->file('country_flag'));
        } else {
            unset($validated['country_flag']);
        }

        $destination->update($validated);

        return response()->json([
            'message' => 'Visa destination updated successfully',
            'data' => (new VisaDestinationResource($destination->fresh()))->resolve(),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * DELETE /api/v1/visa-services?country_name=Turkey
     */
    public function destroy(Request $request)
    {
        $countryName = $request->validate([
            'country_name' => ['required', 'string', 'max:80'],
        ])['country_name'];

        $destination = VisaDestination::query()
            ->where('country_name', $countryName)
            ->firstOrFail();

        $destination->delete();

        return response()->json([
            'message' => 'Visa destination deleted successfully',
            'country_name' => $countryName,
        ]);
    }

    private function saveImage(?UploadedFile $image): ?string
    {
        if (! $image) {
            return null;
        }

        return $image->storeAs('visas', $image->getClientOriginalName(), 'public');
    }

    private function normalizePrice(mixed $price): float
    {
        if (is_array($price)) {
            return (float) ($price['amount'] ?? 0);
        }

        return (float) $price;
    }
}
