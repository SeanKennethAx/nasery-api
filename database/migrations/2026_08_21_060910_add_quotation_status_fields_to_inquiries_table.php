<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->enum('status', [
                'open',
                'receiving_quotations',
                'awarded',
                'cancelled',
            ])
                ->default('open');

            $table->foreignId('awarded_quotation_id')
                ->nullable()
                ->constrained('quotations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropForeign([
                'awarded_quotation_id'
            ]);

            $table->dropColumn([
                'awarded_quotation_id',
                'status',
            ]);
        });
    }
};
