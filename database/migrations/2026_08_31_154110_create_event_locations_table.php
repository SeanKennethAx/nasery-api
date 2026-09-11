<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->unique()
                ->constrained('events')
                ->cascadeOnDelete();

            $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();
            $table->string('google_place_id')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            $table->index('google_place_id');
            $table->index([
                'latitude',
                'longitude',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_locations');
    }
};
