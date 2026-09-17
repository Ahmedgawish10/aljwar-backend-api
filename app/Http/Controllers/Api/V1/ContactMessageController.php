<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ContactMessageResource;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    /**
     * List messages OR single by id.
     *
     * GET /api/v1/contact-messages
     * GET /api/v1/contact-messages?id=1
     * GET /api/v1/contact-messages?status=new&q=ahmed
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:new,read,replied'],
            'service' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! empty($filters['id'])) {
            $message = ContactMessage::query()->findOrFail($filters['id']);

            return response()->json([
                'data' => (new ContactMessageResource($message))->resolve(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        $messages = ContactMessage::query()
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'meta' => $this->paginationMeta($messages),
            'data' => ContactMessageResource::collection($messages->getCollection()),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create from Contact Us form.
     * POST /api/v1/contact-messages
     *
     * Body uses same keys as frontend form.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'service' => ['required', 'string', 'in:tours,hotels,flights,transfers,visa,other'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'privacy' => ['accepted'],
            'locale' => ['nullable', 'string', 'max:10'],
        ]);

        $validated['privacy'] = true;
        $validated['status'] = 'new';

        $message = ContactMessage::create($validated);

        $to = config('mail.contact.to');
        if ($to) {
            Mail::to($to)->send(new ContactMessageReceived($message));
        }

        return response()->json([
            'message' => 'Contact message sent successfully',
            'data' => (new ContactMessageResource($message))->resolve(),
        ], 201);
    }

    /**
     * Update status (or limited fields) for admin.
     * PATCH /api/v1/contact-messages?id=1
     */
    public function update(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $message = ContactMessage::query()->findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:new,read,replied'],
        ]);

        $message->update($validated);

        return response()->json([
            'message' => 'Contact message updated successfully',
            'data' => (new ContactMessageResource($message->fresh()))->resolve(),
        ]);
    }

    /**
     * Delete message.
     * DELETE /api/v1/contact-messages?id=1
     */
    public function destroy(Request $request)
    {
        $id = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ])['id'];

        $message = ContactMessage::query()->findOrFail($id);
        $message->delete();

        return response()->json([
            'message' => 'Contact message deleted successfully',
            'id' => $id,
        ]);
    }
}
