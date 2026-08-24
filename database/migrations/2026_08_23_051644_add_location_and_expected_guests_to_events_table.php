<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('location')
                ->nullable()
                ->after('event_date');

            $table->unsignedInteger('expected_guests')
                ->nullable()
                ->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'location',
                'expected_guests',
            ]);
        });
    }
};
