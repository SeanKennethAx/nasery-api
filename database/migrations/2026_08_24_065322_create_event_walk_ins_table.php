<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_walk_ins', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('event_ticket_type_id')
                ->nullable()
                ->constrained('event_ticket_types')
                ->nullOnDelete();

            $table->string('name');

            $table->string('email')
                ->nullable();

            $table->string('phone')
                ->nullable();

            $table->decimal('amount', 10, 2)
                ->default(0);

            $table->enum('payment_status', [
                'pending',
                'paid',
                'cancelled',
            ])->default('pending');

            $table->timestamp('paid_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'event_id',
                'payment_status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_walk_ins');
    }
};
