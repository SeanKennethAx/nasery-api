<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tickets', function (Blueprint $table) {
            $table->string('attendee_category', 20)
                ->default('paid')
                ->after('source');

            $table->string('payment_status', 20)
                ->nullable()
                ->default(null)
                ->change();
        });

        DB::table('event_tickets')
            ->where('source', 'organizer')
            ->update(['attendee_category' => 'invited']);

        DB::table('event_tickets')
            ->where('source', '!=', 'organizer')
            ->whereIn('event_ticket_type_id', function ($query) {
                $query
                    ->select('id')
                    ->from('event_ticket_types')
                    ->where('price', '<=', 0);
            })
            ->update(['attendee_category' => 'free']);

        if (Schema::hasTable('event_registrations')) {
            Schema::table('event_registrations', function (Blueprint $table) {
                $table->string('attendee_category', 20)
                    ->default('paid')
                    ->after('source');

                $table->string('payment_status', 20)
                    ->nullable()
                    ->default(null)
                    ->change();
            });

            DB::table('event_registrations')
                ->whereIn('event_ticket_type_id', function ($query) {
                    $query
                        ->select('id')
                        ->from('event_ticket_types')
                        ->where('price', '<=', 0);
                })
                ->update(['attendee_category' => 'free']);
        }
    }

    public function down(): void
    {
        Schema::table('event_tickets', function (Blueprint $table) {
            $table->dropColumn('attendee_category');

            $table->string('payment_status', 20)
                ->default('paid')
                ->nullable(false)
                ->change();
        });

        if (
            Schema::hasTable('event_registrations') &&
            Schema::hasColumn('event_registrations', 'attendee_category')
        ) {
            Schema::table('event_registrations', function (Blueprint $table) {
                $table->dropColumn('attendee_category');

                $table->string('payment_status', 20)
                    ->default('paid')
                    ->nullable(false)
                    ->change();
            });
        }
    }
};
