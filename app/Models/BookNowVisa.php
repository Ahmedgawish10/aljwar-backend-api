<?php

namespace App\Models;

use App\Models\Concerns\BookNowTab;
use Illuminate\Database\Eloquent\Model;

class BookNowVisa extends Model
{
    use BookNowTab;

    public const TAB = 'visa';

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'nationality',
        'visa_country',
        'travel_date',
        'special_requests',
        'status',
    ];

    protected $casts = [
        'travel_date' => 'date',
    ];

    public function payload(): array
    {
        return [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'nationality' => $this->nationality,
            'visa_country' => $this->visa_country,
            'travel_date' => $this->dateValue($this->travel_date),
            'special_requests' => $this->special_requests,
            'status' => $this->status,
        ];
    }

    protected function searchColumns(): array
    {
        return ['full_name', 'email', 'phone', 'nationality', 'visa_country', 'special_requests'];
    }
}
