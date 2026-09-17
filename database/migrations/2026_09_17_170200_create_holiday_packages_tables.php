<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('holiday_packages', function (Blueprint $table) {
            $table->id();

            $table->string('external_id')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('destination')->nullable()->index();
            $table->string('duration')->nullable();
            $table->unsignedSmallInteger('days')->default(0);
            $table->unsignedSmallInteger('nights')->default(0);
            $table->unsignedInteger('price_from')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('rating', 3, 1)->default(0);
            $table->unsignedInteger('reviews')->default(0);
            $table->string('tour_type')->nullable();
            $table->string('group_size')->nullable();
            $table->string('badge')->nullable();
            $table->json('categories')->nullable();
            $table->json('features')->nullable();
            $table->text('image')->nullable();
            $table->json('gallery')->nullable();
            $table->text('overview')->nullable();
            $table->json('inclusions_bar')->nullable();
            $table->json('itinerary')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('holiday_package_bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('holiday_package_id')->constrained('holiday_packages')->cascadeOnDelete();
            $table->string('slug')->index();

            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 40);

            $table->string('travelers');
            $table->date('departure');
            $table->date('return_date')->nullable();

            $table->unsignedInteger('price_from')->default(0);
            $table->unsignedInteger('total_price')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending')->index();

            $table->timestamps();

            $table->index(['email', 'created_at']);
            $table->index(['slug', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_package_bookings');
        Schema::dropIfExists('holiday_packages');
    }
};
