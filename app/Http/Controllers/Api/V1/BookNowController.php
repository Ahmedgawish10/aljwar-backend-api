<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookNowRequestResource;
use App\Models\BookNowFlight;
use App\Models\BookNowHotel;
use App\Models\BookNowTour;
use App\Models\BookNowTransfer;
use App\Models\BookNowVisa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookNowController extends Controller
{
    private const TABS = [
        'flights' => BookNowFlight::class,
        'hotels' => BookNowHotel::class,
        'tours' => BookNowTour::class,
        'transfers' => BookNowTransfer::class,
        'visa' => BookNowVisa::class,
    ];

    /**
     * GET /api/v1/book-now
     */
    public function options()
    {
        return response()->json([
            'tabs' => array_keys(self::TABS),
        ]);
    }

    /**
     * GET /api/v1/book-now/{tab}
     * GET /api/v1/book-now/{tab}?id=1
     */
    public function index(Request $request, string $tab)
    {
        $model = $this->modelFor($tab);

        $filters = $request->validate([
            'id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,contacted,confirmed,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! empty($filters['id'])) {
            $item = $model::query()->findOrFail($filters['id']);

            return response()->json([
                'data' => (new BookNowRequestResource($item))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        $items = $model::query()
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($items),
            'data' => BookNowRequestResource::collection($items->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * POST /api/v1/book-now/{tab}
     * Payload = that tab's form fields only.
     */
    public function store(Request $request, string $tab)
    {
        $model = $this->modelFor($tab);

        $validated = $request->validate($this->rulesFor($tab));
        $validated['status'] = 'pending';

        if (array_key_exists('special_requests', $validated) && $validated['special_requests'] === '') {
            $validated['special_requests'] = null;
        }

        $item = $model::create($validated);

        return response()->json([
            'message' => 'Book now request created successfully',
            'data' => (new BookNowRequestResource($item))->resolve(),
        ], 201);
    }

    /**
     * PATCH /api/v1/book-now/{tab}?id=1
     */
    public function update(Request $request, string $tab)
    {
        $model = $this->modelFor($tab);

        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $item = $model::query()->findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:pending,contacted,confirmed,cancelled'],
            'special_requests' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Book now request updated successfully',
            'data' => (new BookNowRequestResource($item->fresh()))->resolve(),
        ]);
    }

    /**
     * DELETE /api/v1/book-now/{tab}?id=1
     */
    public function destroy(Request $request, string $tab)
    {
        $model = $this->modelFor($tab);

        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $item = $model::query()->findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Book now request deleted successfully',
            'id' => $id,
        ]);
    }

    /** @return class-string<Model> */
    private function modelFor(string $tab): string
    {
        if (! isset(self::TABS[$tab])) {
            throw new NotFoundHttpException();
        }

        return self::TABS[$tab];
    }

    /** Same keys as each tab in book.json */
    private function rulesFor(string $tab): array
    {
        $common = [
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:40'],
            'special_requests' => ['nullable', 'string', 'max:500'],
        ];

        $rules = match ($tab) {
            'flights' => [
                'from' => ['required', 'string', 'max:100'],
                'to' => ['required', 'string', 'max:100'],
                'trip_type' => ['required', 'in:roundTrip,oneWay'],
                'departure_date' => ['required', 'date'],
                'return_date' => ['required_if:trip_type,roundTrip', 'nullable', 'date', 'after_or_equal:departure_date'],
                'passengers' => ['required', 'in:1,2,3,4'],
                'class' => ['required', 'in:economy,business,first'],
                'airline' => ['required', 'in:any,emirates,egyptair,qatar,turkish'],
            ],
            'hotels' => [
                'destination' => ['required', 'string', 'max:100'],
                'check_in' => ['required', 'date'],
                'check_out' => ['required', 'date', 'after:check_in'],
                'guests' => ['required', 'in:1,2,3,4'],
                'rooms' => ['required', 'in:1,2,3'],
            ],
            'tours' => [
                'destination' => ['required', 'string', 'max:100'],
                'travel_date' => ['required', 'date'],
                'passengers' => ['required', 'in:1,2,3,4'],
            ],
            'transfers' => [
                'from' => ['required', 'string', 'max:100'],
                'to' => ['required', 'string', 'max:100'],
                'travel_date' => ['required', 'date'],
                'passengers' => ['required', 'in:1,2,3,4'],
            ],
            'visa' => [
                'nationality' => ['required', 'in:egypt,saudi,uae,usa,uk,other'],
                'visa_country' => ['required', 'in:schengen,usa,uk,turkey,uae'],
                'travel_date' => ['required', 'date'],
            ],
            default => [],
        };

        return array_merge($common, $rules);
    }
}
