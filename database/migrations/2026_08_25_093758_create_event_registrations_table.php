<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'event_registrations',
            function (Blueprint $table) {
                $table->id('registration_id');

                $table->foreignId('event_id')
                    ->constrained('events')
                    ->cascadeOnDelete();

                $table->unsignedBigInteger(
                    'attendee_id'
                );

                $table->foreign(
                    'attendee_id'
                )
                    ->references(
                        'attendee_id'
                    )
                    ->on('attendees')
                    ->cascadeOnDelete();

                $table->foreignId(
                    'event_ticket_type_id'
                )
                    ->constrained(
                        'event_ticket_types'
                    )
                    ->restrictOnDelete();

                $table->string('source')
                    ->default('public');

                $table->string('status')
                    ->default('registered');

                $table->string('payment_status')
                    ->default('paid');

                $table->timestamp(
                    'registered_at'
                )
                    ->nullable();

                $table->timestamp(
                    'checked_in_at'
                )
                    ->nullable();

                $table->unsignedBigInteger(
                    'checked_in_by'
                )
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'event_id',
                    'attendee_id',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'event_registrations'
        );
    }
};
