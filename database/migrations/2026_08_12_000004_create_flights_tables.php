<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();

            // Same keys as frontend flights.json
            $table->string('external_id')->unique(); // emirates-1
            $table->string('airline')->index();
            $table->string('airline_name');
            $table->text('airline_logo')->nullable();
            $table->text('image')->nullable();
            $table->string('image_alt')->nullable();
            $table->text('hero_bg')->nullable();
            $table->string('depart_time');
            $table->string('arrive_time');
            $table->string('depart_date')->nullable();
            $table->string('arrive_date')->nullable();
            $table->string('date_label')->nullable();
            $table->boolean('arrive_next_day')->default(false);
            $table->string('duration');
            $table->string('stops')->index(); // direct | oneStop | twoPlus
            $table->string('departure')->index();
            $table->string('arrival')->index();
            $table->string('depart_terminal')->nullable();
            $table->string('arrive_terminal')->nullable();
            $table->string('cabin')->nullable();
            $table->string('departure_slot')->index(); // night | morning | afternoon | evening
            $table->json('price');

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['departure', 'arrival', 'airline']);
            $table->index(['stops', 'departure_slot']);
        });

        Schema::create('flight_bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('flight_id')->constrained('flights')->cascadeOnDelete();
            $table->string('external_id')->index(); // flight id at booking time

            // Same keys as FlightBookingSection form
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);
            $table->string('nationality');
            $table->json('addons')->nullable(); // ["baggage","insurance"]

            $table->decimal('passengers_fare', 10, 2)->default(0);
            $table->decimal('addons_total', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending')->index();

            $table->timestamps();

            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_bookings');
        Schema::dropIfExists('flights');
    }
};
