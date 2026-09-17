<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PopularDestinationBookingResource;
use App\Models\PopularDestination;
use App\Models\PopularDestinationBooking;
use Illuminate\Http\Request;

class PopularDestinationBookingController extends Controller
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
            $booking = PopularDestinationBooking::query()->findOrFail($filters['id']);

            return response()->json([
                'data' => (new PopularDestinationBookingResource($booking))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        $bookings = PopularDestinationBooking::query()
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($bookings),
            'data' => PopularDestinationBookingResource::collection($bookings->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:40'],
            'travel_date' => ['required', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:travel_date'],
            'travelers' => ['required', 'in:1,2,3,4'],
        ]);

        $destination = PopularDestination::query()
            ->active()
            ->where('slug', $validated['slug'])
            ->firstOrFail();

        $booking = PopularDestinationBooking::create([
            'popular_destination_id' => $destination->id,
            'slug' => $destination->slug,
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'travel_date' => $validated['travel_date'],
            'return_date' => $validated['return_date'] ?? null,
            'travelers' => $validated['travelers'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Popular destination booking created successfully',
            'data' => (new PopularDestinationBookingResource($booking))->resolve(),
        ], 201);
    }

    public function update(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $booking = PopularDestinationBooking::query()->findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:pending,confirmed,cancelled'],
            'full_name' => ['sometimes', 'string', 'min:2', 'max:150'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'min:8', 'max:40'],
            'travel_date' => ['sometimes', 'date'],
            'return_date' => ['sometimes', 'nullable', 'date'],
            'travelers' => ['sometimes', 'in:1,2,3,4'],
        ]);

        $booking->update($validated);

        return response()->json([
            'message' => 'Popular destination booking updated successfully',
            'data' => (new PopularDestinationBookingResource($booking->fresh()))->resolve(),
        ]);
    }

    public function destroy(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $booking = PopularDestinationBooking::query()->findOrFail($id);
        $booking->delete();

        return response()->json([
            'message' => 'Popular destination booking deleted successfully',
            'id' => $id,
        ]);
    }
}
