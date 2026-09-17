<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TransferBookingResource;
use App\Models\Transfer;
use App\Models\TransferBooking;
use Illuminate\Http\Request;

class TransferBookingController extends Controller
{
    /**
     * List bookings OR single by id.
     *
     * GET /api/v1/airport-transfers/book
     * GET /api/v1/airport-transfers/book?id=1
     * GET /api/v1/airport-transfers/book?slug=...&status=pending
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'id' => ['nullable', 'integer', 'min:1'],
            'slug' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,confirmed,cancelled'],
            'email' => ['nullable', 'email'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! empty($filters['id'])) {
            $booking = TransferBooking::query()->findOrFail($filters['id']);

            return response()->json([
                'data' => (new TransferBookingResource($booking))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        $bookings = TransferBooking::query()
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($bookings),
            'data' => TransferBookingResource::collection($bookings->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create booking from airport-transfers/[slug]/book page.
     * POST /api/v1/airport-transfers/book
     *
     * Same keys as TransferBookingContent:
     * slug, travel_date, adults, full_name, email, phone, nationality
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
            'travel_date' => ['required', 'date'],
            'adults' => ['required', 'integer', 'min:1', 'max:50'],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:40'],
            'nationality' => ['required', 'string', 'in:egypt,uae,saudi,usa,other'],
        ]);

        $transfer = Transfer::query()
            ->active()
            ->where('slug', $validated['slug'])
            ->firstOrFail();

        $unit_price = (float) ($transfer->price['amount'] ?? 0);
        $currency = $transfer->price['currency'] ?? 'USD';
        $adults = (int) $validated['adults'];

        $booking = TransferBooking::create([
            'transfer_id' => $transfer->id,
            'slug' => $transfer->slug,
            'travel_date' => $validated['travel_date'],
            'adults' => $adults,
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'nationality' => $validated['nationality'],
            'unit_price' => $unit_price,
            'total_price' => $unit_price * $adults,
            'currency' => $currency,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Transfer booking created successfully',
            'data' => (new TransferBookingResource($booking))->resolve(),
        ], 201);
    }

    /**
     * Update booking status.
     * PATCH /api/v1/airport-transfers/book?id=1
     */
    public function update(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $booking = TransferBooking::query()->findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:pending,confirmed,cancelled'],
            'travel_date' => ['sometimes', 'date'],
            'adults' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'full_name' => ['sometimes', 'string', 'min:2', 'max:150'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'min:8', 'max:40'],
            'nationality' => ['sometimes', 'string', 'in:egypt,uae,saudi,usa,other'],
        ]);

        if (isset($validated['adults'])) {
            $validated['total_price'] = (float) $booking->unit_price * (int) $validated['adults'];
        }

        $booking->update($validated);

        return response()->json([
            'message' => 'Transfer booking updated successfully',
            'data' => (new TransferBookingResource($booking->fresh()))->resolve(),
        ]);
    }

    /**
     * Delete booking.
     * DELETE /api/v1/airport-transfers/book?id=1
     */
    public function destroy(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $booking = TransferBooking::query()->findOrFail($id);
        $booking->delete();

        return response()->json([
            'message' => 'Transfer booking deleted successfully',
            'id' => $id,
        ]);
    }
}
