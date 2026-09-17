<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DailyTourBookingResource;
use App\Models\DailyTour;
use App\Models\DailyTourBooking;
use Illuminate\Http\Request;

class DailyTourBookingController extends Controller
{
    /**
     * GET /api/v1/daily-tours/book
     * GET /api/v1/daily-tours/book?id=1
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:pending,confirmed,cancelled'],
            'email' => ['nullable', 'email'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! empty($filters['id'])) {
            $booking = DailyTourBooking::query()->findOrFail($filters['id']);

            return response()->json([
                'data' => (new DailyTourBookingResource($booking))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        $bookings = DailyTourBooking::query()
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($bookings),
            'data' => DailyTourBookingResource::collection($bookings->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create booking from /daily-tours/[slug]/book page.
     * POST /api/v1/daily-tours/book
     *
     * Same keys as TourBookingContent:
     * slug, tour_date, adults, children, infants, full_name, email, phone, nationality
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
            'tour_date' => ['required', 'date'],
            'adults' => ['required', 'integer', 'min:1', 'max:50'],
            'children' => ['nullable', 'integer', 'min:0', 'max:50'],
            'infants' => ['nullable', 'integer', 'min:0', 'max:20'],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:40'],
            'nationality' => ['required', 'string', 'in:egypt,uae,saudi,usa,other'],
        ]);

        $tour = DailyTour::query()
            ->active()
            ->where('slug', $validated['slug'])
            ->firstOrFail();

        $adults = (int) $validated['adults'];
        $children = (int) ($validated['children'] ?? 0);
        $infants = (int) ($validated['infants'] ?? 0);

        $price = $tour->price ?? [];
        $adultPrice = (float) ($price['amount'] ?? 0);
        $childPrice = isset($price['child_amount'])
            ? (float) $price['child_amount']
            : round($adultPrice * 0.7);
        $infantPrice = (float) ($price['infant_amount'] ?? 0);
        $currency = $price['currency'] ?? 'USD';

        $adults_total = $adults * $adultPrice;
        $children_total = $children * $childPrice;
        $infants_total = $infants * $infantPrice;

        $booking = DailyTourBooking::create([
            'daily_tour_id' => $tour->id,
            'slug' => $tour->slug,
            'tour_date' => $validated['tour_date'],
            'adults' => $adults,
            'children' => $children,
            'infants' => $infants,
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'nationality' => $validated['nationality'],
            'adults_total' => $adults_total,
            'children_total' => $children_total,
            'infants_total' => $infants_total,
            'total_price' => $adults_total + $children_total + $infants_total,
            'currency' => $currency,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Daily tour booking created successfully',
            'data' => (new DailyTourBookingResource($booking))->resolve(),
        ], 201);
    }

    /**
     * PATCH /api/v1/daily-tours/book?id=1
     */
    public function update(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $booking = DailyTourBooking::query()->with('dailyTour')->findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:pending,confirmed,cancelled'],
            'full_name' => ['sometimes', 'string', 'min:2', 'max:150'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'min:8', 'max:40'],
            'nationality' => ['sometimes', 'string', 'in:egypt,uae,saudi,usa,other'],
            'tour_date' => ['sometimes', 'date'],
            'adults' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'children' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'infants' => ['sometimes', 'integer', 'min:0', 'max:20'],
        ]);

        if (isset($validated['adults']) || isset($validated['children']) || isset($validated['infants'])) {
            $adults = (int) ($validated['adults'] ?? $booking->adults);
            $children = (int) ($validated['children'] ?? $booking->children);
            $infants = (int) ($validated['infants'] ?? $booking->infants);

            $price = $booking->dailyTour?->price ?? [];
            $adultPrice = (float) ($price['amount'] ?? 0);
            $childPrice = isset($price['child_amount'])
                ? (float) $price['child_amount']
                : round($adultPrice * 0.7);
            $infantPrice = (float) ($price['infant_amount'] ?? 0);

            $adults_total = $adults * $adultPrice;
            $children_total = $children * $childPrice;
            $infants_total = $infants * $infantPrice;

            $validated['adults_total'] = $adults_total;
            $validated['children_total'] = $children_total;
            $validated['infants_total'] = $infants_total;
            $validated['total_price'] = $adults_total + $children_total + $infants_total;
        }

        $booking->update($validated);

        return response()->json([
            'message' => 'Daily tour booking updated successfully',
            'data' => (new DailyTourBookingResource($booking->fresh()))->resolve(),
        ]);
    }

    /**
     * DELETE /api/v1/daily-tours/book?id=1
     */
    public function destroy(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $booking = DailyTourBooking::query()->findOrFail($id);
        $booking->delete();

        return response()->json([
            'message' => 'Daily tour booking deleted successfully',
            'id' => $id,
        ]);
    }
}
