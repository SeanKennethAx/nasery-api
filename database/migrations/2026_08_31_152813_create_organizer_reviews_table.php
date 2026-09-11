<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizer_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organizer_id')
                ->constrained('organizers')
                ->cascadeOnDelete();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->foreignId('event_id')
                ->nullable()
                ->constrained('events')
                ->nullOnDelete();

            $table->foreignId('inquiry_id')
                ->nullable()
                ->constrained('inquiries')
                ->nullOnDelete();

            $table->unsignedTinyInteger('rating');

            $table->text('review')->nullable();

            $table->boolean('is_visible')
                ->default(true);

            $table->timestamps();

            $table->index('organizer_id');
            $table->index('client_id');
            $table->index('event_id');
            $table->index('inquiry_id');
            $table->index('rating');

            $table->unique([
                'organizer_id',
                'client_id',
                'event_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_reviews');
    }
};
