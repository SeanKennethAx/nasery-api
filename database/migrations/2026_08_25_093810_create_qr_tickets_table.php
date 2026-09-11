<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'qr_tickets',
            function (Blueprint $table) {
                $table->id('qr_ticket_id');

                $table->unsignedBigInteger(
                    'registration_id'
                );

                $table->foreign(
                    'registration_id'
                )
                    ->references(
                        'registration_id'
                    )
                    ->on(
                        'event_registrations'
                    )
                    ->cascadeOnDelete();

                $table->string(
                    'qr_token',
                    100
                )
                    ->unique();

                $table->text(
                    'qr_value'
                );

                $table->timestamp(
                    'generated_at'
                )
                    ->nullable();

                $table->timestamp(
                    'emailed_at'
                )
                    ->nullable();

                $table->timestamp(
                    'downloaded_at'
                )
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    'registration_id'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'qr_tickets'
        );
    }
};
