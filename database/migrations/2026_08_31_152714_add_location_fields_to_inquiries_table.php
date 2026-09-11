<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();

            $table->string('google_place_id')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);

            $table->dropColumn([
                'venue_name',
                'venue_address',
                'google_place_id',
                'latitude',
                'longitude',
            ]);
        });
    }
};
