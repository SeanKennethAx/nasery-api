<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tickets', function (Blueprint $table) {
            $table->enum('source', [
                'online',
                'walk_in',
                'organizer',
            ])
                ->default('online')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('event_tickets', function (Blueprint $table) {
            $table->enum('source', [
                'online',
                'walk_in',
            ])
                ->default('online')
                ->change();
        });
    }
};
