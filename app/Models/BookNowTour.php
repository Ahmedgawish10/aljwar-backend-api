<?php

namespace App\Models;

use App\Models\Concerns\BookNowTab;
use Illuminate\Database\Eloquent\Model;

class BookNowTour extends Model
{
    use BookNowTab;

    public const TAB = 'tours';

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'destination',
        'travel_date',
        'passengers',
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
            'destination' => $this->destination,
            'travel_date' => $this->dateValue($this->travel_date),
            'passengers' => $this->passengers,
            'special_requests' => $this->special_requests,
            'status' => $this->status,
        ];
    }

    protected function searchColumns(): array
    {
        return ['full_name', 'email', 'phone', 'destination', 'special_requests'];
    }
}
