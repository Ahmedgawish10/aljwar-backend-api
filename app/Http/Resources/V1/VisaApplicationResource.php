<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisaApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country' => $this->country,
            'visa_type' => $this->visa_type,
            'purpose' => $this->purpose,
            'nationality' => $this->nationality,
            'arrival_date' => optional($this->arrival_date)?->format('Y-m-d'),
            'applicants' => (int) $this->applicants,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'passport_number' => $this->passport_number,
            'date_of_birth' => optional($this->date_of_birth)?->format('Y-m-d'),
            'passport_file' => $this->fileUrl($this->passport_file),
            'photo_file' => $this->fileUrl($this->photo_file),
            'additional_file' => $this->fileUrl($this->additional_file),
            'unit_price' => (float) $this->unit_price,
            'total_price' => (float) $this->total_price,
            'currency' => $this->currency,
            'status' => $this->status,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
