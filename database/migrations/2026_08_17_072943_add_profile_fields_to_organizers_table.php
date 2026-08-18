<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->string('company_name')->nullable();

            $table->string('years_experience')->nullable();

            $table->string('location')->nullable();

            $table->text('bio')->nullable();

            $table->json('tags')->nullable();

            $table->json('specialties')->nullable();

            $table->string('website')->nullable();

            $table->string('facebook')->nullable();

            $table->string('instagram')->nullable();

            $table->string('banner_color')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'years_experience',
                'location',
                'bio',
                'tags',
                'specialties',
                'website',
                'facebook',
                'instagram',
                'banner_color',
            ]);
        });
    }
};
