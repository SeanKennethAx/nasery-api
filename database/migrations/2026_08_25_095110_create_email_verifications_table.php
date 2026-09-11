<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();

            $table->string('email')
                ->index();

            $table->string('event_token', 255)
                ->index();

            $table->string('code_hash');

            $table->string('verification_token', 128)
                ->nullable()
                ->unique();

            $table->timestamp('expires_at');

            $table->timestamp('verified_at')
                ->nullable();

            $table->timestamp('last_sent_at')
                ->nullable();

            $table->unsignedTinyInteger('attempts')
                ->default(0);

            $table->timestamps();

            $table->index([
                'email',
                'event_token',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'email_verifications'
        );
    }
};
