<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\FlightBookingResource;
use App\Models\Flight;
use App\Models\FlightBooking;
use Illuminate\Http\Request;

class FlightBookingController extends Controller
{
    /** Same addon prices as src/data/flights.json meta.addons */
    private const ADDON_PRICES = [
        'baggage' => 45,
        'insurance' => 22,
    ];

    /**
     * GET /api/v1/flights/book
     * GET /api/v1/flights/book?id=1
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
            'external_id' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,confirmed,cancelled'],
            'email' => ['nullable', 'email'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! empty($filters['id'])) {
            $booking = FlightBooking::query()->findOrFail($filters['id']);

            return response()->json([
                'data' => (new FlightBookingResource($booking))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        $bookings = FlightBooking::query()
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($bookings),
            'data' => FlightBookingResource::collection($bookings->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create booking from /flights/[id] detail page.
     * POST /api/v1/flights/book
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:100'],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:40'],
            'adults' => ['required', 'integer', 'min:1', 'max:9'],
            'children' => ['nullable', 'integer', 'min:0', 'max:6'],
            'nationality' => ['required', 'string', 'in:egypt,uae,saudi,usa'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['string', 'in:baggage,insurance'],
        ]);

        $flight = Flight::query()
            ->active()
            ->where('external_id', $validated['id'])
            ->firstOrFail();

        $adults = (int) $validated['adults'];
        $children = (int) ($validated['children'] ?? 0);
        $unit_price = (float) ($flight->price['amount'] ?? 0);
        $currency = $flight->price['currency'] ?? 'USD';

        $passengers_fare = ($unit_price * $adults) + (round($unit_price * 0.75) * $children);

        $addons = $validated['addons'] ?? [];
        $addons_total = collect($addons)->sum(fn ($addonId) => self::ADDON_PRICES[$addonId] ?? 0);

        $booking = FlightBooking::create([
            'flight_id' => $flight->id,
            'external_id' => $flight->external_id,
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'adults' => $adults,
            'children' => $children,
            'nationality' => $validated['nationality'],
            'addons' => $addons,
            'passengers_fare' => $passengers_fare,
            'addons_total' => $addons_total,
            'total_price' => $passengers_fare + $addons_total,
            'currency' => $currency,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Flight booking created successfully',
            'data' => (new FlightBookingResource($booking))->resolve(),
        ], 201);
    }

    /**
     * PATCH /api/v1/flights/book?id=1
     */
    public function update(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $booking = FlightBooking::query()->with('flight')->findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:pending,confirmed,cancelled'],
            'full_name' => ['sometimes', 'string', 'min:2', 'max:150'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'min:8', 'max:40'],
            'adults' => ['sometimes', 'integer', 'min:1', 'max:9'],
            'children' => ['sometimes', 'integer', 'min:0', 'max:6'],
            'nationality' => ['sometimes', 'string', 'in:egypt,uae,saudi,usa'],
            'addons' => ['sometimes', 'array'],
            'addons.*' => ['string', 'in:baggage,insurance'],
        ]);

        if (isset($validated['adults']) || isset($validated['children']) || isset($validated['addons'])) {
            $adults = (int) ($validated['adults'] ?? $booking->adults);
            $children = (int) ($validated['children'] ?? $booking->children);
            $unit_price = (float) ($booking->flight?->price['amount'] ?? 0);
            $addons = $validated['addons'] ?? $booking->addons ?? [];

            $passengers_fare = ($unit_price * $adults) + (round($unit_price * 0.75) * $children);
            $addons_total = collect($addons)->sum(fn ($addonId) => self::ADDON_PRICES[$addonId] ?? 0);

            $validated['passengers_fare'] = $passengers_fare;
            $validated['addons_total'] = $addons_total;
            $validated['total_price'] = $passengers_fare + $addons_total;
        }

        $booking->update($validated);

        return response()->json([
            'message' => 'Flight booking updated successfully',
            'data' => (new FlightBookingResource($booking->fresh()))->resolve(),
        ]);
    }

    /**
     * DELETE /api/v1/flights/book?id=1
     */
    public function destroy(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $booking = FlightBooking::query()->findOrFail($id);
        $booking->delete();

        return response()->json([
            'message' => 'Flight booking deleted successfully',
            'id' => $id,
        ]);
    }
}
