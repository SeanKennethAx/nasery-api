<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE event_tickets MODIFY status VARCHAR(32) NOT NULL DEFAULT 'valid'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('event_tickets')
            ->where('status', 'pending_approval')
            ->update(['status' => 'cancelled']);

        DB::statement(
            "ALTER TABLE event_tickets MODIFY status ENUM('valid', 'cancelled', 'used') NOT NULL DEFAULT 'valid'"
        );
    }
};
