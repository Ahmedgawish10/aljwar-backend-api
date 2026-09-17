<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\HolidayPackageBookingResource;
use App\Models\HolidayPackage;
use App\Models\HolidayPackageBooking;
use Illuminate\Http\Request;

class HolidayPackageBookingController extends Controller
{
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
            $booking = HolidayPackageBooking::query()->findOrFail($filters['id']);

            return response()->json([
                'data' => (new HolidayPackageBookingResource($booking))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        $bookings = HolidayPackageBooking::query()
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($bookings),
            'data' => HolidayPackageBookingResource::collection($bookings->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:40'],
            'travelers' => ['required', 'in:1,2,3,4'],
            'departure' => ['required', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:departure'],
        ]);

        $package = HolidayPackage::query()
            ->active()
            ->where('slug', $validated['slug'])
            ->firstOrFail();

        $price_from = (int) $package->price_from;
        $travelers = (int) $validated['travelers'];

        $booking = HolidayPackageBooking::create([
            'holiday_package_id' => $package->id,
            'slug' => $package->slug,
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'travelers' => $validated['travelers'],
            'departure' => $validated['departure'],
            'return_date' => $validated['return_date'] ?? null,
            'price_from' => $price_from,
            'total_price' => $price_from * $travelers,
            'currency' => $package->currency ?? 'USD',
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Holiday package booking created successfully',
            'data' => (new HolidayPackageBookingResource($booking))->resolve(),
        ], 201);
    }

    public function update(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $booking = HolidayPackageBooking::query()->with('holidayPackage')->findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:pending,confirmed,cancelled'],
            'full_name' => ['sometimes', 'string', 'min:2', 'max:150'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'min:8', 'max:40'],
            'travelers' => ['sometimes', 'in:1,2,3,4'],
            'departure' => ['sometimes', 'date'],
            'return_date' => ['sometimes', 'nullable', 'date'],
        ]);

        if (isset($validated['travelers'])) {
            $price_from = (int) ($booking->holidayPackage?->price_from ?? $booking->price_from);
            $validated['price_from'] = $price_from;
            $validated['total_price'] = $price_from * (int) $validated['travelers'];
        }

        $booking->update($validated);

        return response()->json([
            'message' => 'Holiday package booking updated successfully',
            'data' => (new HolidayPackageBookingResource($booking->fresh()))->resolve(),
        ]);
    }

    public function destroy(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $booking = HolidayPackageBooking::query()->findOrFail($id);
        $booking->delete();

        return response()->json([
            'message' => 'Holiday package booking deleted successfully',
            'id' => $id,
        ]);
    }
}
