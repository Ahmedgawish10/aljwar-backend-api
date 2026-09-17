<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_now_flights', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 40);
            $table->string('from');
            $table->string('to');
            $table->string('trip_type');
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->string('passengers');
            $table->string('class');
            $table->string('airline');
            $table->text('special_requests')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index(['email', 'created_at']);
        });

        Schema::create('book_now_hotels', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 40);
            $table->string('destination');
            $table->date('check_in');
            $table->date('check_out');
            $table->string('guests');
            $table->string('rooms');
            $table->text('special_requests')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index(['email', 'created_at']);
        });

        Schema::create('book_now_tours', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 40);
            $table->string('destination');
            $table->date('travel_date');
            $table->string('passengers');
            $table->text('special_requests')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index(['email', 'created_at']);
        });

        Schema::create('book_now_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 40);
            $table->string('from');
            $table->string('to');
            $table->date('travel_date');
            $table->string('passengers');
            $table->text('special_requests')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index(['email', 'created_at']);
        });

        Schema::create('book_now_visas', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 40);
            $table->string('nationality');
            $table->string('visa_country');
            $table->date('travel_date');
            $table->text('special_requests')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_now_visas');
        Schema::dropIfExists('book_now_transfers');
        Schema::dropIfExists('book_now_tours');
        Schema::dropIfExists('book_now_hotels');
        Schema::dropIfExists('book_now_flights');
    }
};
