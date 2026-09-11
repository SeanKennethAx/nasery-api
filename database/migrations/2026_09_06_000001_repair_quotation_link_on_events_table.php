<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasColumn('events', 'offer_id') &&
            ! Schema::hasColumn('events', 'quotation_id')
        ) {
            Schema::table('events', function (Blueprint $table) {
                $table->renameColumn('offer_id', 'quotation_id');
            });

            return;
        }

        if (! Schema::hasColumn('events', 'quotation_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->unsignedBigInteger('quotation_id')
                    ->nullable()
                    ->after('inquiry_id');

                $table->index('quotation_id');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasColumn('events', 'quotation_id') &&
            ! Schema::hasColumn('events', 'offer_id')
        ) {
            Schema::table('events', function (Blueprint $table) {
                $table->renameColumn('quotation_id', 'offer_id');
            });
        }
    }
};
