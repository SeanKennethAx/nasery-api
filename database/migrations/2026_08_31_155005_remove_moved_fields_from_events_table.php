<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'location',
                'venue_name',
                'venue_address',
                'google_place_id',
                'latitude',
                'longitude',
                'public_registration',
                'public_registration_token',
                'require_approval',
                'waitlist_enabled',
                'contact_name',
                'contact_email',
                'contact_phone',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('location')->nullable();
            $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();
            $table->string('google_place_id')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('public_registration')->default(false);
            $table->string('public_registration_token', 64)->nullable();
            $table->boolean('require_approval')->default(false);
            $table->boolean('waitlist_enabled')->default(false);
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
        });
    }
};
