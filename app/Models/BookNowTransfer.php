<?php

namespace App\Models;

use App\Models\Concerns\BookNowTab;
use Illuminate\Database\Eloquent\Model;

class BookNowTransfer extends Model
{
    use BookNowTab;

    public const TAB = 'transfers';

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'from',
        'to',
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
            'from' => $this->from,
            'to' => $this->to,
            'travel_date' => $this->dateValue($this->travel_date),
            'passengers' => $this->passengers,
            'special_requests' => $this->special_requests,
            'status' => $this->status,
        ];
    }

    protected function searchColumns(): array
    {
        return ['full_name', 'email', 'phone', 'from', 'to', 'special_requests'];
    }
}
