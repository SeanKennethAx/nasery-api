<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('public_registration')
                ->default(false);

            $table->boolean('require_approval')
                ->default(false);

            $table->boolean('waitlist_enabled')
                ->default(false);

            $table->string('contact_name')
                ->nullable();

            $table->string('contact_email')
                ->nullable();

            $table->string('contact_phone')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'public_registration',
                'require_approval',
                'waitlist_enabled',
                'contact_name',
                'contact_email',
                'contact_phone',
            ]);
        });
    }
};
