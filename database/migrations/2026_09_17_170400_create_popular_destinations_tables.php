<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('popular_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('country')->nullable();
            $table->json('categories')->nullable();
            $table->unsignedInteger('price_from')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('featured')->default(false);
            $table->text('image')->nullable();
            $table->json('gallery')->nullable();
            $table->text('overview')->nullable();
            $table->json('highlights')->nullable();
            $table->json('info')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['featured', 'price_from']);
            $table->index(['is_active']);
        });

        Schema::create('popular_destination_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('popular_destination_id')
                ->constrained('popular_destinations')
                ->cascadeOnDelete();
            $table->string('slug')->index();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 40);
            $table->date('travel_date');
            $table->date('return_date')->nullable();
            $table->string('travelers')->default('2');
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popular_destination_bookings');
        Schema::dropIfExists('popular_destinations');
    }
};
