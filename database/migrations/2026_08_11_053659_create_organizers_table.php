<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('company_name')
                ->nullable();

            $table->string('years_experience', 100)
                ->nullable();

            $table->string('location', 500)
                ->nullable();

            $table->string('google_place_id')
                ->nullable();

            $table->decimal('latitude', 10, 7)
                ->nullable();

            $table->decimal('longitude', 10, 7)
                ->nullable();

            $table->unsignedSmallInteger('service_radius_km')
                ->default(25);

            $table->text('bio')
                ->nullable();

            $table->json('tags')
                ->nullable();

            $table->json('specialties')
                ->nullable();

            $table->string('website')
                ->nullable();

            $table->string('facebook')
                ->nullable();

            $table->string('instagram')
                ->nullable();

            $table->string('banner_color', 20)
                ->nullable();

            $table->timestamps();

            $table->index([
                'latitude',
                'longitude',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizers');
    }
};
