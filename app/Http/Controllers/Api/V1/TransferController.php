<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TransferDetailResource;
use App\Http\Resources\V1\TransferListResource;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransferController extends Controller
{
    /**
     * List (minimal) OR single detail by query.
     *
     * GET /api/v1/transfers
     * GET /api/v1/transfers?slug=...  → full detail
     * GET /api/v1/transfers?q=...     → filtered list
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'slug' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:100'],
            'destination' => ['nullable'],
            'experience' => ['nullable'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:recommended,priceLow,priceHigh,popularity,newest'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if (! empty($filters['slug'])) {
            $transfer = Transfer::query()
                ->active()
                ->where('slug', $filters['slug'])
                ->firstOrFail();

            return response()->json(
                (new TransferDetailResource($transfer))->resolve(),
                200,
                [],
                JSON_UNESCAPED_SLASHES
            );
        }

        $perPage = (int) ($filters['per_page'] ?? 12);

        $transfers = Transfer::query()
            ->active()
            ->select(Transfer::LIST_COLUMNS)
            ->filter($filters)
            ->sortBy($filters['sort'] ?? 'recommended')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($transfers),
            'data' => TransferListResource::collection($transfers->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create airport transfer.
     * POST /api/v1/airport-transfers
     * POST /api/v1/transfers
     */
    public function store(Request $request)
    {
        $input = $request->input('attributes', $request->all());

        $validated = Validator::make($input, [
            'id' => ['nullable', 'string', 'max:100', Rule::unique('transfers', 'external_id')],
            'slug' => ['required', 'string', 'max:255', 'unique:transfers,slug'],
            'name' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:100'],
            'duration_minutes' => ['required', 'integer', 'min:0'],
            'experience' => ['required', 'string', 'in:fastTrack,privateTransfer'],
            'main_image' => ['nullable'],
            'run' => ['nullable', 'string', 'max:100'],
            'group_size' => ['nullable', 'string', 'max:100'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['nullable'],
            'description' => ['nullable', 'string'],
            'inclusions' => ['nullable', 'array'],
            'inclusions.*' => ['string'],
            'exclusions' => ['nullable', 'array'],
            'exclusions.*' => ['string'],
            'meeting_point' => ['nullable', 'string'],
            'things_to_remember' => ['nullable', 'array'],
            'things_to_remember.*' => ['string'],
            'price' => ['required', 'array'],
            'price.amount' => ['required', 'numeric', 'min:0'],
            'price.currency' => ['nullable', 'string', 'size:3'],
            'price.formatted' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ])->validate();

        $amount = (float) $validated['price']['amount'];
        $currency = $validated['price']['currency'] ?? 'USD';
        $validated['price'] = [
            'amount' => $amount,
            'currency' => $currency,
            'formatted' => $validated['price']['formatted'] ?? ('$ ' . number_format($amount, 0)),
        ];

        $externalId = $validated['id'] ?? $validated['slug'];
        unset($validated['id']);

        $validated['main_image'] = $this->saveImage($request->file('main_image'));
        $validated['gallery'] = $this->saveGallery($request, $validated['gallery'] ?? []);

        $transfer = Transfer::create(array_merge($validated, [
            'external_id' => $externalId,
            'destination_label' => $validated['destination'],
            'duration_minutes' => $validated['duration_minutes'] ?? 0,
            'run' => $validated['run'] ?? 'Everyday',
            'group_size' => $validated['group_size'] ?? 'Private Service',
            'gallery' => $validated['gallery'] ?? [],
            'description' => $validated['description'] ?? null,
            'inclusions' => $validated['inclusions'] ?? [],
            'exclusions' => $validated['exclusions'] ?? [],
            'things_to_remember' => $validated['things_to_remember'] ?? [],
            'is_active' => $validated['is_active'] ?? true,
        ]));

        return response()->json([
            'message' => 'Transfer created successfully',
            'data' => (new TransferDetailResource($transfer))->resolve(),
        ], 201, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * PATCH /api/v1/airport-transfers?slug=...
     * PATCH /api/v1/transfers?slug=...
     */
    public function update(Request $request)
    {
        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        $transfer = Transfer::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $input = $request->input('attributes', $request->except(['slug']));

        $validated = Validator::make($input, [
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('transfers', 'slug')->ignore($transfer->id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'destination' => ['sometimes', 'string', 'max:100'],
            'duration_minutes' => ['sometimes', 'integer', 'min:0'],
            'experience' => ['sometimes', 'string', 'in:fastTrack,privateTransfer'],
            'main_image' => ['sometimes', 'nullable'],
            'run' => ['sometimes', 'nullable', 'string', 'max:100'],
            'group_size' => ['sometimes', 'nullable', 'string', 'max:100'],
            'gallery' => ['sometimes', 'nullable', 'array'],
            'gallery.*' => ['nullable'],
            'description' => ['sometimes', 'nullable', 'string'],
            'inclusions' => ['sometimes', 'nullable', 'array'],
            'inclusions.*' => ['string'],
            'exclusions' => ['sometimes', 'nullable', 'array'],
            'exclusions.*' => ['string'],
            'meeting_point' => ['sometimes', 'nullable', 'string'],
            'things_to_remember' => ['sometimes', 'nullable', 'array'],
            'things_to_remember.*' => ['string'],
            'price' => ['sometimes', 'array'],
            'price.amount' => ['sometimes', 'numeric', 'min:0'],
            'price.currency' => ['sometimes', 'string', 'size:3'],
            'price.formatted' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ])->validate();

        if (isset($validated['price'])) {
            $merged = array_merge($transfer->price ?? [], $validated['price']);
            $amount = (float) ($merged['amount'] ?? 0);
            if (! array_key_exists('formatted', $validated['price'])) {
                $merged['formatted'] = '$ ' . number_format($amount, 0);
            }
            $validated['price'] = $merged;
        }

        if ($request->hasFile('main_image')) {
            $validated['main_image'] = $this->saveImage($request->file('main_image'));
        }

        if ($request->hasFile('gallery') || array_key_exists('gallery', $validated)) {
            $validated['gallery'] = $this->saveGallery($request, $validated['gallery'] ?? []);
        }

        if (isset($validated['destination'])) {
            $validated['destination_label'] = $validated['destination'];
        }

        $transfer->update($validated);

        return response()->json([
            'message' => 'Transfer updated successfully',
            'data' => (new TransferDetailResource($transfer->fresh()))->resolve(),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * DELETE /api/v1/transfers?slug=...
     */
    public function destroy(Request $request)
    {
        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        $transfer = Transfer::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $transfer->delete();

        return response()->json([
            'message' => 'Transfer deleted successfully',
            'slug' => $slug,
        ]);
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
            return $image->storeAs('transfers', $image->getClientOriginalName(), 'public');
        }

        if (! is_string($image) || ! str_starts_with($image, 'data:image')) {
            return $image;
        }

        $ext = str_contains($image, 'png') ? 'png' : (str_contains($image, 'webp') ? 'webp' : 'jpg');
        $name = uniqid().'.'.$ext;
        Storage::disk('public')->put('transfers/'.$name, base64_decode(explode(',', $image, 2)[1] ?? ''));

        return 'transfers/'.$name;
    }
}
