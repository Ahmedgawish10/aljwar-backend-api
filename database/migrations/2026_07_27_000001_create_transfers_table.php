<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();

            // Same keys as frontend JSON (src/data/transfers.json)
            $table->string('external_id')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('destination')->index();
            $table->string('destination_label');
            $table->unsignedSmallInteger('duration_minutes')->default(0);
            $table->string('experience')->index();
            $table->json('price'); // { amount, currency, formatted? }
            $table->string('main_image')->nullable();

            // Detail fields
            $table->string('run')->nullable();
            $table->string('group_size')->nullable();
            $table->json('gallery')->nullable();
            $table->text('description')->nullable();
            $table->json('inclusions')->nullable();
            $table->json('exclusions')->nullable();
            $table->text('meeting_point')->nullable();
            $table->json('things_to_remember')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->text('info_voucher')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['destination', 'experience']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
