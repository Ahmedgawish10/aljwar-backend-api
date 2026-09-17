<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();

            $table->string('external_id')->unique(); 
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('destination')->index();
            $table->text('description')->nullable();
            $table->text('main_image')->nullable();
            $table->text('hero_image')->nullable();
            $table->json('gallery')->nullable();
            $table->unsignedTinyInteger('stars')->default(0)->index();
            $table->decimal('rating', 3, 1)->default(0)->index();
            $table->string('review_label_key')->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->json('review_breakdown')->nullable();
            $table->string('property_type')->nullable()->index(); 
            $table->boolean('best_seller')->default(false);
            $table->json('amenities')->nullable();
            $table->json('price');
            $table->string('check_in')->nullable();
            $table->string('check_out')->nullable();
            $table->string('pets')->nullable();
            $table->json('things_to_remember')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['destination', 'stars']);
            $table->index(['property_type', 'best_seller']);
        });

        Schema::create('hotel_bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('slug')->index();

            // Same keys as HotelBookingContent form
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('nights')->default(1);
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->string('nationality'); // egypt | uae | saudi | usa | other

            $table->decimal('night_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending')->index();

            $table->timestamps();

            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_bookings');
        Schema::dropIfExists('hotels');
    }
};
