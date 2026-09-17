<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_tour_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('image')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('daily_tours', function (Blueprint $table) {
            $table->foreignId('daily_tour_category_id')
                ->nullable()
                ->after('id')
                ->constrained('daily_tour_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_tours', function (Blueprint $table) {
            $table->dropConstrainedForeignId('daily_tour_category_id');
        });

        Schema::dropIfExists('daily_tour_categories');
    }
};
