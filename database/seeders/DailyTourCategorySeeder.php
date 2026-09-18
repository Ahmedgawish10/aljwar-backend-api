<?php

namespace Database\Seeders;

use App\Models\DailyTourCategory;
use Illuminate\Database\Seeder;

class DailyTourCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'egypt-classic-tours',
                'name' => 'Egypt Classic Tours',
                'description' => 'Classic Egypt Tours will take you back to live Egypt\'s ancient history and visit its top attractions. Explore the best selection of tours to Cairo, Luxor and Aswan on a magical Nile Cruise, Hurghada, Alexandria and more.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/FB_IMG_1690968173525.webp',
                'icon' => 'fa-solid fa-landmark',
            ],
            [
                'slug' => 'honeymoon-packages',
                'name' => 'Egypt Honeymoon Packages',
                'description' => 'Romantic Egypt honeymoon packages for couples.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/teaser-aegypt-neu.webp',
                'icon' => 'fa-solid fa-heart',
            ],
            [
                'slug' => 'cheap-packages',
                'name' => 'Budget Egypt Tours',
                'description' => 'Affordable Egypt tours designed for budget travelers.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/the-pictures-belong-to.webp',
                'icon' => 'fa-solid fa-tag',
            ],
            [
                'slug' => 'short-breaks',
                'name' => 'Egypt Short Breaks',
                'description' => 'Short Egypt getaways for limited travel time.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/Alabaster_Mosque.webp',
                'icon' => 'fa-solid fa-clock',
            ],
            [
                'slug' => 'spiritual-tours-to-egypt',
                'name' => 'Egypt Spiritual Tours',
                'description' => 'Spiritual and religious tours across Egypt.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/Sinai-431-min-1024x614.webp',
                'icon' => 'fa-solid fa-mosque',
            ],
            [
                'slug' => 'red-sea-tours',
                'name' => 'Egypt Vacations with Red Sea',
                'description' => 'Egypt holidays combining sightseeing with Red Sea resorts.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/21102022153145177.webp',
                'icon' => 'fa-solid fa-water',
            ],
            [
                'slug' => 'egypt-and-jordan-tours',
                'name' => 'Egypt and Jordan Tours',
                'description' => 'Combined tours covering Egypt and Jordan.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/4d2db13facb70514392e6d967ac730fc.webp',
                'icon' => 'fa-solid fa-globe',
            ],
            [
                'slug' => 'egypt-holy-land-tours',
                'name' => 'Egypt Holy Land Tours',
                'description' => 'Holy Land itineraries through Egypt\'s religious sites.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/Garden_in_a_Monastery_of_Wadi_Natrun_BASE_IMAGE_370X500_.webp',
                'icon' => 'fa-solid fa-church',
            ],
            [
                'slug' => 'egypt-holiday-deals',
                'name' => 'Egypt Travel Deals',
                'description' => 'Seasonal Egypt travel deals and holiday offers.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/b35fb1a4d97eecb375f3621dbebccf79.webp',
                'icon' => 'fa-solid fa-percent',
            ],
            [
                'slug' => 'easter-holiday-deals',
                'name' => 'Easter Tours to Egypt',
                'description' => 'Easter holiday tours to Egypt.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/ff25eb64e10bea7405e0ea93439320d9.webp',
                'icon' => 'fa-solid fa-egg',
            ],
            [
                'slug' => 'egypt-new-year-deals',
                'name' => 'Egypt New Year Tours & Christmas Packages',
                'description' => 'Christmas and New Year packages in Egypt.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/GIZA5_base.webp',
                'icon' => 'fa-solid fa-gift',
            ],
            [
                'slug' => 'egypt-luxury-tours',
                'name' => 'Egypt Luxury Tours',
                'description' => 'Luxury Egypt tours with premium stays and private service.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/Fayoum-Qaroun-Lake-3.webp',
                'icon' => 'fa-solid fa-crown',
            ],
            [
                'slug' => 'desert-safari',
                'name' => 'Egypt Desert Safari Tours',
                'description' => 'Desert safari tours across Egypt\'s oases and dunes.',
                'image' => 'https://ibisegypttours.com/media/catalog/category/252712826_636884330641405_7270102643586774634_n.webp',
                'icon' => 'fa-solid fa-sun',
            ],
        ];

        $slugs = [];

        foreach ($categories as $index => $item) {
            $slugs[] = $item['slug'];

            DailyTourCategory::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'image' => $item['image'],
                    'icon' => $item['icon'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        DailyTourCategory::query()
            ->whereNotIn('slug', $slugs)
            ->delete();
    }
}
