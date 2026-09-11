<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registration_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->unique()
                ->constrained('events')
                ->cascadeOnDelete();

            $table->boolean('public_registration')
                ->default(false);

            $table->string('public_registration_token', 64)
                ->nullable()
                ->unique();

            $table->boolean('require_approval')
                ->default(false);

            $table->boolean('waitlist_enabled')
                ->default(false);

            $table->string('contact_name')
                ->nullable();

            $table->string('contact_email')
                ->nullable();

            $table->string('contact_phone')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registration_settings');
    }
};
