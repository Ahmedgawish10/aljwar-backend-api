<?php

namespace Database\Seeders;

use App\Models\Newsletter;
use Illuminate\Database\Seeder;

class NewsletterSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['email' => 'sara.hassan@example.com', 'status' => 'subscribed'],
            ['email' => 'omar.ali@example.com', 'status' => 'subscribed'],
            ['email' => 'noura.khaled@example.com', 'status' => 'unsubscribed'],
        ];

        foreach ($items as $item) {
            Newsletter::updateOrCreate(
                ['email' => $item['email']],
                ['status' => $item['status']]
            );
        }
    }
}
