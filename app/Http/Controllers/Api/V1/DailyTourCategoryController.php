<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DailyTourCategoryResource;
use App\Models\DailyTourCategory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DailyTourCategoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'slug' => ['nullable', 'string', 'max:255'],
            'with_tours' => ['nullable'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = DailyTourCategory::query()
            ->listed()
            ->when($request->boolean('with_tours'), fn ($q) => $q->withActiveTours());

        if (! empty($filters['slug'])) {
            $category = $query->where('slug', $filters['slug'])->first();

            if (! $category) {
                return response()->json([
                    'message' => 'Category not found',
                ], 404);
            }

            return response()->json([
                'data' => (new DailyTourCategoryResource($category))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 12);

        $categories = $query->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($categories),
            'data' => DailyTourCategoryResource::collection($categories->getCollection()),
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

        $category = DailyTourCategory::create(array_merge($data, [
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? (int) DailyTourCategory::query()->max('sort_order') + 1,
        ]));

        return response()->json([
            'message' => 'Tour category created successfully',
            'data' => $this->payload($category),
        ], 201);
    }

    public function update(Request $request)
    {
        $this->acceptPatchFiles($request);

        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        $category = DailyTourCategory::query()->where('slug', $slug)->firstOrFail();
        $data = $this->validateAttributes($request->except(['slug']), isCreate: false, categoryId: $category->id);

        if ($request->hasFile('image')) {
            $data['image'] = $this->saveImage($request->file('image'));
        } else {
            unset($data['image']);
        }

        $category->update($data);

        return response()->json([
            'message' => 'Tour category updated successfully',
            'data' => $this->payload($category->fresh()),
        ]);
    }

    public function destroy(Request $request)
    {
        $slug = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ])['slug'];

        DailyTourCategory::query()->where('slug', $slug)->firstOrFail()->delete();

        return response()->json([
            'message' => 'Tour category deleted successfully',
            'slug' => $slug,
        ]);
    }

    private function payload(DailyTourCategory $category): array
    {
        $category->loadCount(['tours' => fn ($q) => $q->active()]);

        return (new DailyTourCategoryResource($category))->resolve();
    }

    private function validateAttributes(array $input, bool $isCreate, ?int $categoryId = null): array
    {
        $slugRule = $isCreate
            ? ['required', 'string', 'max:255', Rule::unique('daily_tour_categories', 'slug')]
            : ['sometimes', 'string', 'max:255', Rule::unique('daily_tour_categories', 'slug')->ignore($categoryId)];

        return Validator::make($input, [
            'slug' => $slugRule,
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ])->validate();
    }

    private function saveImage(?UploadedFile $image): ?string
    {
        if (! $image) {
            return null;
        }

        return $image->storeAs('tour-categories', $image->getClientOriginalName(), 'public');
    }
}
