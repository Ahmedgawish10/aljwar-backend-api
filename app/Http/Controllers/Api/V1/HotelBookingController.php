<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\HotelBookingResource;
use App\Models\Hotel;
use App\Models\HotelBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HotelBookingController extends Controller
{
    /**
     * GET /api/v1/hotels/book
     * GET /api/v1/hotels/book?id=1
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:pending,completed,confirmed,cancelled'],
            'email' => ['nullable', 'email'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! empty($filters['id'])) {
            $booking = HotelBooking::query()->with('hotel')->findOrFail($filters['id']);

            return response()->json([
                'data' => (new HotelBookingResource($booking))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        $bookings = HotelBooking::query()
            ->with('hotel')
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($bookings),
            'data' => HotelBookingResource::collection($bookings->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create booking from /hotels/[slug]/book page.
     * POST /api/v1/hotels/book
     *
     * Same keys as HotelBookingContent:
     * slug, check_in, check_out, full_name, email, phone, nationality
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:40'],
            'nationality' => ['required', 'string', 'in:egypt,uae,saudi,usa,other'],
        ]);

        $hotel = Hotel::query()
            ->active()
            ->where('slug', $validated['slug'])
            ->firstOrFail();

        $check_in = Carbon::parse($validated['check_in'])->startOfDay();
        $check_out = Carbon::parse($validated['check_out'])->startOfDay();
        $nights = max(1, (int) $check_in->diffInDays($check_out));

        $night_price = (float) ($hotel->price['amount'] ?? 0);
        $currency = $hotel->price['currency'] ?? 'USD';

        $booking = HotelBooking::create([
            'hotel_id' => $hotel->id,
            'slug' => $hotel->slug,
            'check_in' => $check_in->toDateString(),
            'check_out' => $check_out->toDateString(),
            'nights' => $nights,
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'nationality' => $validated['nationality'],
            'night_price' => $night_price,
            'total_price' => $night_price * $nights,
            'currency' => $currency,
            'status' => 'pending',
        ]);

        $booking->load('hotel');

        return response()->json([
            'message' => 'Hotel booking created successfully',
            'data' => (new HotelBookingResource($booking))->resolve(),
        ], 201);
    }

    /**
     * PATCH /api/v1/hotels/book?id=1
     */
    public function update(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $booking = HotelBooking::query()->with('hotel')->findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:pending,completed,confirmed,cancelled'],
            'full_name' => ['sometimes', 'string', 'min:2', 'max:150'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'min:8', 'max:40'],
            'nationality' => ['sometimes', 'string', 'in:egypt,uae,saudi,usa,other'],
            'check_in' => ['sometimes', 'date'],
            'check_out' => ['sometimes', 'date'],
        ]);

        if (isset($validated['check_in']) || isset($validated['check_out'])) {
            $check_in = Carbon::parse($validated['check_in'] ?? $booking->check_in)->startOfDay();
            $check_out = Carbon::parse($validated['check_out'] ?? $booking->check_out)->startOfDay();

            if ($check_out->lte($check_in)) {
                return response()->json([
                    'message' => 'check_out must be after check_in',
                    'errors' => ['check_out' => ['check_out must be after check_in']],
                ], 422);
            }

            $nights = max(1, (int) $check_in->diffInDays($check_out));
            $night_price = (float) ($booking->hotel?->price['amount'] ?? $booking->night_price);

            $validated['check_in'] = $check_in->toDateString();
            $validated['check_out'] = $check_out->toDateString();
            $validated['nights'] = $nights;
            $validated['night_price'] = $night_price;
            $validated['total_price'] = $night_price * $nights;
        }

        $booking->update($validated);

        return response()->json([
            'message' => 'Hotel booking updated successfully',
            'data' => (new HotelBookingResource($booking->fresh()->load('hotel')))->resolve(),
        ]);
    }

    /**
     * DELETE /api/v1/hotels/book?id=1
     */
    public function destroy(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $booking = HotelBooking::query()->findOrFail($id);
        $booking->delete();

        return response()->json([
            'message' => 'Hotel booking deleted successfully',
            'id' => $id,
        ]);
    }
}
