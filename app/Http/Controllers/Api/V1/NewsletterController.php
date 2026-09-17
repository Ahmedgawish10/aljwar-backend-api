<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\NewsletterResource;
use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:subscribed,unsubscribed'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! empty($filters['id'])) {
            $item = Newsletter::query()->findOrFail($filters['id']);

            return response()->json([
                'data' => (new NewsletterResource($item))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        $items = Newsletter::query()
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($items),
            'data' => NewsletterResource::collection($items->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $item = Newsletter::updateOrCreate(
            ['email' => strtolower($validated['email'])],
            [
                'status' => 'subscribed',
            ]
        );

        return response()->json([
            'message' => 'Subscribed to newsletter successfully',
            'data' => (new NewsletterResource($item))->resolve(),
        ], 201);
    }

    public function update(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $item = Newsletter::query()->findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:subscribed,unsubscribed'],
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Newsletter subscription updated successfully',
            'data' => (new NewsletterResource($item->fresh()))->resolve(),
        ]);
    }

    public function destroy(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $item = Newsletter::query()->findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Newsletter subscription deleted successfully',
            'id' => $id,
        ]);
    }
}
