<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\HotelDetailResource;
use App\Http\Resources\V1\HotelListResource;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class HotelController extends Controller
{
    /**
     * GET /api/v1/hotels
     * GET /api/v1/hotels?slug=marriott-mena-house-cairo
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'slug' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:100'],
            'destination' => ['nullable'],
            'stars' => ['nullable'],
            'property_type' => ['nullable'],
            'amenities' => ['nullable'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'best_seller' => ['nullable'],
            'sort' => ['nullable', 'in:recommended,priceLow,priceHigh,rating,newest'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if (!empty($filters['slug'])) {
            $hotel = Hotel::query()
                ->active()
                ->where('slug', $filters['slug'])
                ->firstOrFail();

            return response()->json(
                (new HotelDetailResource($hotel))->resolve()
            );
        }

        $perPage = (int) ($filters['per_page'] ?? 12);

        $hotels = Hotel::query()
            ->active()
            ->select(Hotel::LIST_COLUMNS)
            ->filter($filters)
            ->sortBy($filters['sort'] ?? 'recommended')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($hotels),
            'data' => HotelListResource::collection(
                $hotels->getCollection()
            ),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * POST /api/v1/hotels
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('hotels', 'external_id'),
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hotels', 'slug'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'main_image' => ['nullable'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['nullable'],
            'stars' => ['nullable', 'integer', 'min:1', 'max:5'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'review_label_key' => ['nullable', 'string', 'max:50'],
            'review_count' => ['nullable', 'integer', 'min:0'],
            'review_breakdown' => ['nullable', 'array'],
            'property_type' => [
                'nullable',
                'string',
                'in:hotels,resorts,apartments,villas',
            ],
            'best_seller' => ['nullable', 'boolean'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'check_in' => ['nullable', 'string', 'max:50'],
            'check_out' => ['nullable', 'string', 'max:50'],
            'pets' => ['nullable', 'string', 'max:100'],
            'things_to_remember' => ['nullable', 'array'],
            'things_to_remember.*' => ['string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $externalId = $data['id'] ?? $data['slug'];
        unset($data['id']);

        if ($request->hasFile('main_image')) {
            $data['main_image'] = $this->saveImage(
                $request->file('main_image')
            );
        }

        if ($request->hasFile('gallery')) {
            $data['gallery'] = $this->saveGallery($request);
        }

        $data['external_id'] = $externalId;
        $data['is_active'] = $data['is_active'] ?? true;

        $hotel = Hotel::create($data);

        return response()->json([
            'message' => 'Hotel created successfully',
            'data' => (new HotelDetailResource(
                $hotel->fresh()
            ))->resolve(),
        ], 201);
    }

    /**
     * PATCH /api/v1/hotels?slug=marriott-mena-house-cairo
     */
    public function update(Request $request)
    {
        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        $hotel = Hotel::where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'id' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('hotels', 'external_id')
                    ->ignore($hotel->id),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'destination' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'main_image' => ['nullable'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['nullable'],
            'stars' => ['nullable', 'integer', 'min:1', 'max:5'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'review_label_key' => ['nullable', 'string', 'max:50'],
            'review_count' => ['nullable', 'integer', 'min:0'],
            'review_breakdown' => ['nullable', 'array'],
            'property_type' => [
                'nullable',
                'string',
                'in:hotels,resorts,apartments,villas',
            ],
            'best_seller' => ['nullable', 'boolean'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'check_in' => ['nullable', 'string', 'max:50'],
            'check_out' => ['nullable', 'string', 'max:50'],
            'pets' => ['nullable', 'string', 'max:100'],
            'things_to_remember' => ['nullable', 'array'],
            'things_to_remember.*' => ['string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        if (isset($data['id'])) {
            $data['external_id'] = $data['id'];
            unset($data['id']);
        }

        if ($request->hasFile('main_image')) {
            $data['main_image'] = $this->saveImage(
                $request->file('main_image')
            );
        }

        if ($request->hasFile('gallery')) {
            $data['gallery'] = $this->saveGallery($request);
        }

        $hotel->update($data);

        return response()->json([
            'message' => 'Hotel updated successfully',
            'data' => (new HotelDetailResource(
                $hotel->fresh()
            ))->resolve(),
        ]);
    }

    /**
     * DELETE /api/v1/hotels?slug=marriott-mena-house-cairo
     */
    public function destroy(Request $request)
    {
        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        $hotel = Hotel::where('slug', $slug)->firstOrFail();
        $hotel->delete();

        return response()->json([
            'message' => 'Hotel deleted successfully',
            'slug' => $slug,
        ]);
    }

    /**
     * Save gallery images.
     */
    private function saveGallery(Request $request): array
    {
        $files = $request->file('gallery', []);

        if (!is_array($files)) {
            $files = $files ? [$files] : [];
        }

        $saved = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $path = $this->saveImage($file);
                if ($path) {
                    $saved[] = $path;
                }
            }
        }

        return $saved;
    }

    /**
     * Save uploaded image or return existing image path.
     */
    private function saveImage(mixed $image): mixed
    {
        if ($image instanceof UploadedFile) {
            return $image->storeAs(
                'hotels',
                uniqid() . '_' . $image->getClientOriginalName(),
                'public'
            );
        }

        if (
            !is_string($image) ||
            !str_starts_with($image, 'data:image')
        ) {
            return $image;
        }

        $extension = str_contains($image, 'png')
            ? 'png'
            : (str_contains($image, 'webp') ? 'webp' : 'jpg');

        $name = uniqid() . '.' . $extension;
        $base64 = explode(',', $image, 2)[1] ?? '';

        Storage::disk('public')->put(
            'hotels/' . $name,
            base64_decode($base64)
        );

        return 'hotels/' . $name;
    }
}
