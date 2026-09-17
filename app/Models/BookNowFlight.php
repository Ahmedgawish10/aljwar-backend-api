<?php

namespace App\Models;

use App\Models\Concerns\BookNowTab;
use Illuminate\Database\Eloquent\Model;

class BookNowFlight extends Model
{
    use BookNowTab;

    public const TAB = 'flights';

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'from',
        'to',
        'trip_type',
        'departure_date',
        'return_date',
        'passengers',
        'class',
        'airline',
        'special_requests',
        'status',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
    ];

    public function payload(): array
    {
        return [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'from' => $this->from,
            'to' => $this->to,
            'trip_type' => $this->trip_type,
            'departure_date' => $this->dateValue($this->departure_date),
            'return_date' => $this->dateValue($this->return_date),
            'passengers' => $this->passengers,
            'class' => $this->class,
            'airline' => $this->airline,
            'special_requests' => $this->special_requests,
            'status' => $this->status,
        ];
    }

    protected function searchColumns(): array
    {
        return ['full_name', 'email', 'phone', 'from', 'to', 'airline', 'special_requests'];
    }
}
