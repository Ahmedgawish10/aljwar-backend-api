<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PopularDestinationCategoryResource;
use App\Models\PopularDestinationCategory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PopularDestinationCategoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'slug' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = PopularDestinationCategory::query()->orderByDesc('id');

        if (! empty($filters['slug'])) {
            $category = $query->where('slug', $filters['slug'])->first();

            if (! $category) {
                return response()->json([
                    'message' => 'Category not found',
                ], 404);
            }

            PopularDestinationCategory::attachDestinationCounts(collect([$category]));

            return response()->json([
                'data' => (new PopularDestinationCategoryResource($category))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 10);

        $categories = $query->paginate($perPage);
        PopularDestinationCategory::attachDestinationCounts($categories->getCollection());

        return response()->json([
            'meta' => $this->paginationMeta($categories),
            'data' => PopularDestinationCategoryResource::collection($categories->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function store(Request $request)
    {
        if ($request->query('slug')) {
            return $this->update($request);
        }

        $data = $this->validateAttributes($request->all(), isCreate: true);

        if ($request->hasFile('image')) {
            $data['image'] = $this->saveImage($request->file('image'));
        }

        PopularDestinationCategory::query()->increment('sort_order');

        $category = PopularDestinationCategory::create(array_merge($data, [
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => 0,
        ]));

        $category->destinations_count = 0;

        return response()->json([
            'message' => 'Destination category created successfully',
            'data' => (new PopularDestinationCategoryResource($category))->resolve(),
        ], 201);
    }

    public function update(Request $request)
    {
        $this->acceptPatchFiles($request);

        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        $category = PopularDestinationCategory::query()->where('slug', $slug)->firstOrFail();
        $data = $this->validateAttributes($request->except(['slug']), isCreate: false, categoryId: $category->id);

        if ($request->hasFile('image')) {
            $data['image'] = $this->saveImage($request->file('image'));
        } else {
            unset($data['image']);
        }

        $category->update($data);
        $fresh = $category->fresh();
        PopularDestinationCategory::attachDestinationCounts(collect([$fresh]));

        return response()->json([
            'message' => 'Destination category updated successfully',
            'data' => (new PopularDestinationCategoryResource($fresh))->resolve(),
        ]);
    }

    public function destroy(Request $request)
    {
        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        PopularDestinationCategory::query()->where('slug', $slug)->firstOrFail()->delete();

        return response()->json([
            'message' => 'Destination category deleted successfully',
            'slug' => $slug,
        ]);
    }

    private function validateAttributes(array $input, bool $isCreate, ?int $categoryId = null): array
    {
        $slugRule = $isCreate
            ? ['required', 'string', 'max:255', Rule::unique('popular_destination_categories', 'slug')]
            : ['sometimes', 'string', 'max:255', Rule::unique('popular_destination_categories', 'slug')->ignore($categoryId)];

        return Validator::make($input, [
            'slug' => $slugRule,
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable'],
            'price_from' => ['nullable', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ])->validate();
    }

    private function saveImage(?UploadedFile $image): ?string
    {
        if (! $image) {
            return null;
        }

        return $image->storeAs('destination-categories', $image->getClientOriginalName(), 'public');
    }
}
