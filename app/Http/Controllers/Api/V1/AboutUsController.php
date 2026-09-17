<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AboutUsResource;
use App\Models\AboutUs;
use Illuminate\Http\Request;

class AboutUsController extends Controller
{
    public function show()
    {
        return response()->json(
            (new AboutUsResource(AboutUs::query()->first() ?? new AboutUs))->resolve()
        );
    }

    public function store(Request $request)
    {
        return $this->save($request, AboutUs::query()->first() ?? new AboutUs);
    }

    public function update(Request $request)
    {
        return $this->save($request, AboutUs::query()->firstOrFail());
    }

    private function save(Request $request, AboutUs $aboutUs)
    {
        $data = $request->validate([
            'main_image' => ['nullable', 'image', 'max:10240'],
            'overline' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'info' => ['nullable', 'string'],
            'stats' => ['nullable'],
            'values' => ['nullable'],
        ]);

        foreach (['stats', 'values'] as $key) {
            if (is_string($data[$key] ?? null)) {
                $data[$key] = json_decode($data[$key], true);
            }
        }

        if ($request->hasFile('main_image')) {
            $file = $request->file('main_image');
            $data['main_image'] = $file->storeAs('about-us', uniqid().'_'.$file->getClientOriginalName(), 'public');
        } else {
            unset($data['main_image']);
        }

        $aboutUs->fill($data)->save();

        return response()->json([
            'message' => 'About us content saved successfully',
            'data' => (new AboutUsResource($aboutUs))->resolve(),
        ]);
    }
}
