<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organizer_id')
                ->constrained('organizers')
                ->cascadeOnDelete();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();

            $table->foreignId('inquiry_id')
                ->nullable()
                ->constrained('inquiries')
                ->nullOnDelete();

            $table->foreignId('quotation_id')
                ->nullable()
                ->constrained('quotations')
                ->nullOnDelete();

            $table->string('name');

            $table->string('event_type');

            $table->text('description')
                ->nullable();

            $table->date('event_date')
                ->nullable();

            $table->unsignedInteger('expected_guests')
                ->nullable();

            $table->time('start_time')
                ->nullable();

            $table->time('end_time')
                ->nullable();

            $table->string('status')
                ->default('draft');

            $table->timestamps();

            $table->index('event_date');
            $table->index('event_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
