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
            ])
                ->default('online')
                ->after('qr_token');

            $table->enum('payment_status', [
                'pending',
                'paid',
                'refunded',
            ])
                ->default('paid')
                ->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('event_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'source',
                'payment_status',
            ]);
        });
    }
};
