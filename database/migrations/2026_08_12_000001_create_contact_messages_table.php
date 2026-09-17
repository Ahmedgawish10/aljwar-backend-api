<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();

            // Same keys as frontend contact form
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->string('country');
            $table->string('subject');
            $table->string('service');
            $table->text('message');
            $table->boolean('privacy')->default(true);

            // Admin helpers
            $table->string('status')->default('new')->index(); // new | read | replied
            $table->string('locale', 10)->nullable();
            $table->timestamps();

            $table->index(['email', 'created_at']);
            $table->index(['service', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
