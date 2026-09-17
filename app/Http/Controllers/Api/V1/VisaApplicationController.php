<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\VisaApplicationResource;
use App\Models\VisaApplication;
use App\Models\VisaDestination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VisaApplicationController extends Controller
{
    /**
     * List applications OR single by id.
     * GET /api/v1/visa-services/apply
     * GET /api/v1/visa-services/apply?id=1
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:50'],
            'visa_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:pending,processing,approved,rejected,cancelled'],
            'email' => ['nullable', 'email'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! empty($filters['id'])) {
            $application = VisaApplication::query()->findOrFail($filters['id']);

            return response()->json([
                'data' => (new VisaApplicationResource($application))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        $applications = VisaApplication::query()
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($applications),
            'data' => VisaApplicationResource::collection($applications->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create application from /visa-services/apply
     * POST /api/v1/visa-services/apply
     *
     * Same keys as VisaApplyPageContent form.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'country' => ['required', 'string', 'max:80', 'exists:visas,country_name'],
            'visa_type' => ['required', 'string', 'in:tourist,business,transit,umrah,eVisa'],
            'purpose' => ['required', 'string', 'in:vacation,meeting,familyVisit'],
            'nationality' => ['required', 'string', 'in:egyptian,saudi,british,american,other'],
            'arrival_date' => ['required', 'date'],
            'applicants' => ['required', 'integer', 'min:1', 'max:20'],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:40'],
            'passport_number' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'passport' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'additional' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $destination = VisaDestination::query()
            ->active()
            ->where('country_name', $validated['country'])
            ->first();

        $unit_price = (float) ($destination?->price ?? 0);
        $applicants = (int) $validated['applicants'];

        $application = VisaApplication::create([
            'visa_id' => $destination?->id,
            'country' => $validated['country'],
            'visa_type' => $validated['visa_type'],
            'purpose' => $validated['purpose'],
            'nationality' => $validated['nationality'],
            'arrival_date' => $validated['arrival_date'],
            'applicants' => $applicants,
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'passport_number' => $validated['passport_number'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'unit_price' => $unit_price,
            'total_price' => $unit_price * $applicants,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $this->storeDocumentFiles($request, $application);

        return response()->json([
            'message' => 'Visa application created successfully',
            'data' => (new VisaApplicationResource($application->fresh()))->resolve(),
        ], 201);
    }

    /**
     * PATCH /api/v1/visa-services/apply?id=1
     */
    public function update(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $application = VisaApplication::query()->findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:pending,processing,approved,rejected,cancelled'],
            'country' => ['sometimes', 'string', 'max:80', 'exists:visas,country_name'],
            'visa_type' => ['sometimes', 'string', 'in:tourist,business,transit,umrah,eVisa'],
            'purpose' => ['sometimes', 'string', 'in:vacation,meeting,familyVisit'],
            'nationality' => ['sometimes', 'string', 'in:egyptian,saudi,british,american,other'],
            'arrival_date' => ['sometimes', 'date'],
            'applicants' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'full_name' => ['sometimes', 'string', 'min:2', 'max:150'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'min:8', 'max:40'],
            'passport_number' => ['sometimes', 'string', 'max:50'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'passport' => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'photo' => ['sometimes', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'additional' => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if (isset($validated['applicants'])) {
            $validated['total_price'] = (float) $application->unit_price * (int) $validated['applicants'];
        }

        if (isset($validated['country'])) {
            $destination = VisaDestination::query()
                ->where('country_name', $validated['country'])
                ->first();

            if ($destination) {
                $validated['visa_id'] = $destination->id;
                $validated['unit_price'] = (float) $destination->price;
                $applicants = (int) ($validated['applicants'] ?? $application->applicants);
                $validated['total_price'] = (float) $destination->price * $applicants;
            }
        }

        unset($validated['passport'], $validated['photo'], $validated['additional']);

        $application->update($validated);
        $this->storeDocumentFiles($request, $application);

        return response()->json([
            'message' => 'Visa application updated successfully',
            'data' => (new VisaApplicationResource($application->fresh()))->resolve(),
        ]);
    }

    /**
     * DELETE /api/v1/visa-services/apply?id=1
     */
    public function destroy(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $application = VisaApplication::query()->findOrFail($id);
        $application->delete();

        return response()->json([
            'message' => 'Visa application deleted successfully',
            'id' => $id,
        ]);
    }

    private function storeDocumentFiles(Request $request, VisaApplication $application): void
    {
        $map = [
            'passport' => 'passport_file',
            'photo' => 'photo_file',
            'additional' => 'additional_file',
        ];

        $updates = [];

        foreach ($map as $input => $column) {
            if (! $request->hasFile($input)) {
                continue;
            }

            if ($application->{$column}) {
                Storage::disk('public')->delete($application->{$column});
            }

            $updates[$column] = $request->file($input)->store(
                'visa-applications/' . $application->id,
                'public'
            );
        }

        if ($updates) {
            $application->update($updates);
        }
    }
}
