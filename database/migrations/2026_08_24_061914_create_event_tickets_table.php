<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_tickets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('event_ticket_type_id')
                ->nullable()
                ->constrained('event_ticket_types')
                ->nullOnDelete();

            $table->string('attendee_name');
            $table->string('attendee_email')
                ->nullable();

            $table->string('qr_token', 100)
                ->unique();

            $table->enum('status', [
                'valid',
                'cancelled',
                'used',
            ])->default('valid');

            $table->timestamp('checked_in_at')
                ->nullable();

            $table->foreignId('checked_in_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'event_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_tickets');
    }
};
