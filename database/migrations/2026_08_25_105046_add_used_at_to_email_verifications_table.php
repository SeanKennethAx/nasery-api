<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'email_verifications',
            function (Blueprint $table) {
                $table->timestamp(
                    'used_at'
                )
                    ->nullable()
                    ->after(
                        'verified_at'
                    )
                    ->index();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'email_verifications',
            function (Blueprint $table) {
                $table->dropColumn(
                    'used_at'
                );
            }
        );
    }
};
