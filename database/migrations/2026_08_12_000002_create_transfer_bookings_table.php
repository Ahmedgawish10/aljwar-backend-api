<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transfer_bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transfer_id')->constrained('transfers')->cascadeOnDelete();
            $table->string('slug')->index(); // transfer slug at booking time

            // Same keys as frontend TransferBookingContent
            $table->date('travel_date');
            $table->unsignedSmallInteger('adults')->default(1);
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->string('nationality')->default('egypt');

            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            $table->string('status')->default('pending')->index(); // pending | confirmed | cancelled
            $table->timestamps();

            $table->index(['email', 'created_at']);
            $table->index(['travel_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_bookings');
    }
};
