<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->string('location', 500)
                ->nullable();

            $table->string('google_place_id', 255)
                ->nullable();

            $table->decimal('latitude', 10, 7)
                ->nullable();

            $table->decimal('longitude', 10, 7)
                ->nullable();

            $table->unsignedSmallInteger('service_radius_km')
                ->default(25);

            $table->index([
                'latitude',
                'longitude',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropIndex([
                'latitude',
                'longitude',
            ]);

            $table->dropColumn([
                'location',
                'google_place_id',
                'latitude',
                'longitude',
                'service_radius_km',
            ]);
        });
    }
};
