<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $events = DB::table('events')->get();

        foreach ($events as $event) {
            DB::table('event_locations')->updateOrInsert(
                [
                    'event_id' => $event->id,
                ],
                [
                    'venue_name' => $event->venue_name ?? $event->location,
                    'venue_address' => $event->venue_address ?? $event->location,
                    'google_place_id' => $event->google_place_id,
                    'latitude' => $event->latitude,
                    'longitude' => $event->longitude,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('event_registration_settings')->updateOrInsert(
                [
                    'event_id' => $event->id,
                ],
                [
                    'public_registration' => $event->public_registration,
                    'public_registration_token' => $event->public_registration_token,
                    'require_approval' => $event->require_approval,
                    'waitlist_enabled' => $event->waitlist_enabled,
                    'contact_name' => $event->contact_name,
                    'contact_email' => $event->contact_email,
                    'contact_phone' => $event->contact_phone,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('event_locations')->delete();
        DB::table('event_registration_settings')->delete();
    }
};
