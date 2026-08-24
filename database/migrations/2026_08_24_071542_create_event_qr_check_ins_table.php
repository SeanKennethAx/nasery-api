<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_qr_check_ins', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('event_ticket_id')
                ->constrained('event_tickets')
                ->cascadeOnDelete();

            $table->foreignId('checked_in_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('checked_in_at');

            $table->timestamps();

            $table->index([
                'event_id',
                'checked_in_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'event_qr_check_ins'
        );
    }
};
