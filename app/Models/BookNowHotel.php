<?php

namespace App\Models;

use App\Models\Concerns\BookNowTab;
use Illuminate\Database\Eloquent\Model;

class BookNowHotel extends Model
{
    use BookNowTab;

    public const TAB = 'hotels';

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'destination',
        'check_in',
        'check_out',
        'guests',
        'rooms',
        'special_requests',
        'status',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
    ];

    public function payload(): array
    {
        return [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'destination' => $this->destination,
            'check_in' => $this->dateValue($this->check_in),
            'check_out' => $this->dateValue($this->check_out),
            'guests' => $this->guests,
            'rooms' => $this->rooms,
            'special_requests' => $this->special_requests,
            'status' => $this->status,
        ];
    }

    protected function searchColumns(): array
    {
        return ['full_name', 'email', 'phone', 'destination', 'special_requests'];
    }
}
