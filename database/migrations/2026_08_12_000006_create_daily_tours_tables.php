<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_tours', function (Blueprint $table) {
            $table->id();

            // Same keys as frontend tours.json + tour-details.json
            $table->string('external_id')->unique(); // giza-museum
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('destination')->index();
            $table->string('destination_label')->nullable();
            $table->text('description')->nullable(); // list card blurb
            $table->text('overview')->nullable(); // detail page
            $table->text('image')->nullable();
            $table->text('hero_image')->nullable();
            $table->json('gallery')->nullable();
            $table->decimal('rating', 3, 1)->default(0)->index();
            $table->unsignedInteger('review_count')->default(0);
            $table->string('tour_type')->index(); // cultural | adventure | historical | cruise
            $table->string('tour_type_label')->nullable();
            $table->string('tour_code')->nullable();
            $table->string('duration');
            $table->unsignedSmallInteger('duration_hours')->default(0)->index();
            $table->string('duration_key')->index(); // halfDay | fullDay | multiDay
            $table->string('run')->nullable();
            $table->string('group_size')->nullable();
            $table->boolean('best_seller')->default(false);
            $table->string('pickup_time')->nullable();
            $table->string('languages')->nullable();
            $table->json('highlights')->nullable();
            $table->json('itinerary')->nullable();
            $table->json('inclusions')->nullable();
            $table->json('exclusions')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->text('info_voucher')->nullable();
            $table->json('price');

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['destination', 'tour_type']);
            $table->index(['duration_key', 'best_seller']);
        });

        Schema::create('daily_tour_bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('daily_tour_id')->constrained('daily_tours')->cascadeOnDelete();
            $table->string('slug')->index();

            // Same keys as TourBookingContent
            $table->date('tour_date');
            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);
            $table->unsignedSmallInteger('infants')->default(0);
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->string('nationality');

            $table->decimal('adults_total', 10, 2)->default(0);
            $table->decimal('children_total', 10, 2)->default(0);
            $table->decimal('infants_total', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending')->index();

            $table->timestamps();

            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_tour_bookings');
        Schema::dropIfExists('daily_tours');
    }
};
