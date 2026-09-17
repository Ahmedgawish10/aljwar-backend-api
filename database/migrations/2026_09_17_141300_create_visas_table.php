<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('visas', function (Blueprint $table) {
            $table->id();
            $table->string('country_name')->unique();
            $table->string('country_image')->nullable();
            $table->string('country_flag')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('visa_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visa_id')
                ->nullable()
                ->constrained('visas')
                ->nullOnDelete();

            $table->string('country')->index();
            $table->string('visa_type');
            $table->string('purpose');
            $table->string('nationality');
            $table->date('arrival_date');
            $table->unsignedSmallInteger('applicants')->default(1);
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->string('passport_number');
            $table->date('date_of_birth')->nullable();
            $table->string('passport_file')->nullable();
            $table->string('photo_file')->nullable();
            $table->string('additional_file')->nullable();

            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending')->index();

            $table->timestamps();

            $table->index(['email', 'created_at']);
            $table->index(['country', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_applications');
        Schema::dropIfExists('visas');
    }
};
