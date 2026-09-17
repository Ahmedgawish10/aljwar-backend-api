<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\FlightDetailResource;
use App\Http\Resources\V1\FlightListResource;
use App\Models\Flight;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FlightController extends Controller
{
    /**
     * List (minimal) OR single detail by id.
     *
     * GET /api/v1/flights
     * GET /api/v1/flights?id=emirates-1
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'id' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'airline' => ['nullable'],
            'stops' => ['nullable'],
            'departure' => ['nullable', 'string', 'max:50'],
            'arrival' => ['nullable', 'string', 'max:50'],
            'departure_slot' => ['nullable'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:recommended,priceLow,priceHigh,duration'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if (! empty($filters['id'])) {
            $flight = Flight::query()
                ->active()
                ->where('external_id', $filters['id'])
                ->firstOrFail();

            return response()->json([
                'data' => (new FlightDetailResource($flight))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 12);

        $flights = Flight::query()
            ->active()
            ->select(Flight::LIST_COLUMNS)
            ->filter($filters)
            ->sortBy($filters['sort'] ?? 'recommended')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($flights),
            'data' => FlightListResource::collection($flights->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * POST /api/v1/flights
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $validated = $this->validateFlightAttributes($input, isCreate: true);

        $validated['external_id'] = $validated['id'];
        unset($validated['id']);

        $validated['price'] = $this->normalizePrice($validated['price'] ?? 0);

        $validated['image'] = $this->saveImage($request->file('image') ?? $validated['image'] ?? null);
        $validated['airline_logo'] = $this->saveImage($request->file('airline_logo') ?? $validated['airline_logo'] ?? null);

        $flight = Flight::create(array_merge($validated, [
            'is_active' => $validated['is_active'] ?? true,
        ]));

        return response()->json([
            'message' => 'Flight created successfully',
            'data' => (new FlightDetailResource($flight))->resolve(),
        ], 201);
    }

    /**
     * PATCH /api/v1/flights?id=emirates-1
     */
    public function update(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'string', 'max:100'],
        ])['id'];

        $flight = Flight::query()
            ->where('external_id', $id)
            ->firstOrFail();

        $input = $request->except(['id']);

        $validated = $this->validateFlightAttributes($input, isCreate: false, flightId: $flight->id);

        if (isset($validated['id'])) {
            $validated['external_id'] = $validated['id'];
            unset($validated['id']);
        }

        if (array_key_exists('price', $validated)) {
            $validated['price'] = $this->normalizePrice($validated['price'], $flight->price ?? []);
        }

        if (array_key_exists('image', $validated) || $request->hasFile('image')) {
            $validated['image'] = $this->saveImage($request->file('image') ?? $validated['image'] ?? null);
        }
        if (array_key_exists('airline_logo', $validated) || $request->hasFile('airline_logo')) {
            $validated['airline_logo'] = $this->saveImage($request->file('airline_logo') ?? $validated['airline_logo'] ?? null);
        }

        $flight->update($validated);

        return response()->json([
            'message' => 'Flight updated successfully',
            'data' => (new FlightDetailResource($flight->fresh()))->resolve(),
        ]);
    }

    /**
     * DELETE /api/v1/flights?id=emirates-1
     */
    public function destroy(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'string', 'max:100'],
        ])['id'];

        $flight = Flight::query()
            ->where('external_id', $id)
            ->firstOrFail();

        $flight->delete();

        return response()->json([
            'message' => 'Flight deleted successfully',
            'id' => $id,
        ]);
    }

    private function validateFlightAttributes(array $input, bool $isCreate, ?int $flightId = null): array
    {
        $idRule = $isCreate
            ? ['required', 'string', 'max:100', Rule::unique('flights', 'external_id')]
            : ['sometimes', 'string', 'max:100', Rule::unique('flights', 'external_id')->ignore($flightId)];

        return Validator::make($input, [
            'id' => $idRule,
            'airline' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:50'],
            'airline_name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:100'],
            'airline_logo' => ['nullable'],
            'image' => ['nullable'],
            'image_alt' => ['nullable', 'string', 'max:100'],
            'hero_bg' => ['nullable', 'string'],
            'depart_time' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:10'],
            'arrive_time' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:10'],
            'depart_date' => ['nullable', 'string', 'max:50'],
            'arrive_date' => ['nullable', 'string', 'max:50'],
            'date_label' => ['nullable', 'string', 'max:100'],
            'arrive_next_day' => ['nullable', 'boolean'],
            'duration' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:20'],
            'stops' => [$isCreate ? 'required' : 'sometimes', 'string', 'in:direct,oneStop,twoPlus'],
            'departure' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:50'],
            'arrival' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:50'],
            'depart_terminal' => ['nullable', 'string', 'max:50'],
            'arrive_terminal' => ['nullable', 'string', 'max:50'],
            'cabin' => ['nullable', 'string', 'max:50'],
            'departure_slot' => [$isCreate ? 'required' : 'sometimes', 'string', 'in:night,morning,afternoon,evening'],
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

        $price = is_array($price) ? $price : [];
        $merged = array_merge($current, $price);

        return [
            'amount' => (float) ($merged['amount'] ?? 0),
            'currency' => $merged['currency'] ?? 'USD',
        ];
    }

    private function saveImage(mixed $image): mixed
    {
        if ($image instanceof UploadedFile) {
            return $image->storeAs('flights', $image->getClientOriginalName(), 'public');
        }

        if (! is_string($image) || ! str_starts_with($image, 'data:image')) {
            return $image;
        }

        $ext = str_contains($image, 'png') ? 'png' : (str_contains($image, 'webp') ? 'webp' : 'jpg');
        $name = uniqid().'.'.$ext;
        Storage::disk('public')->put('flights/'.$name, base64_decode(explode(',', $image, 2)[1] ?? ''));

        return 'flights/'.$name;
    }
}
