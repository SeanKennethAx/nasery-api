<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicOrganizerDirectoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->default('organizer');
            $table->timestamps();
        });

        Schema::create('organizers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
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
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id');
            $table->string('name');
            $table->string('event_type');
            $table->text('description')->nullable();
            $table->date('event_date')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('event_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id');
            $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();
            $table->timestamps();
        });

        Schema::create('event_registration_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id');
            $table->boolean('public_registration')->default(false);
            $table->string('public_registration_token')->nullable();
            $table->boolean('require_approval')->default(false);
            $table->boolean('waitlist_enabled')->default(false);
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->timestamps();
        });

        Schema::create('event_ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id');
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamps();
        });

        Schema::create('organizer_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id');
            $table->unsignedTinyInteger('rating');
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'firstname' => 'Ana',
            'lastname' => 'Reyes',
            'email' => 'private@example.test',
            'phone' => '+639171234567',
            'role' => 'organizer',
        ]);

        DB::table('organizers')->insert([
            'id' => 7,
            'user_id' => 1,
            'company_name' => 'Reyes Events',
            'years_experience' => '8 years',
            'location' => 'Cebu City, Cebu',
            'bio' => 'We create thoughtful celebrations.',
            'tags' => json_encode(['Wedding']),
            'specialties' => json_encode(['Wedding planning']),
            'website' => 'https://example.test',
            'banner_color' => '#123456',
        ]);

        DB::table('events')->insert([
            [
                'id' => 10,
                'organizer_id' => 7,
                'name' => 'Garden Wedding',
                'event_type' => 'Wedding',
                'description' => 'A completed garden celebration.',
                'event_date' => '2026-01-10',
                'status' => 'completed',
            ],
            [
                'id' => 11,
                'organizer_id' => 7,
                'name' => 'Private Future Event',
                'event_type' => 'Corporate',
                'description' => 'Must not be public.',
                'event_date' => '2027-01-10',
                'status' => 'confirmed',
            ],
        ]);

        DB::table('event_locations')->insert([
            'event_id' => 10,
            'venue_name' => 'Cebu Garden',
        ]);

        DB::table('event_registration_settings')->insert([
            [
                'event_id' => 10,
                'public_registration' => true,
                'public_registration_token' => 'garden-registration',
            ],
            [
                'event_id' => 11,
                'public_registration' => false,
                'public_registration_token' => 'private-registration',
            ],
        ]);

        DB::table('event_ticket_types')->insert([
            'event_id' => 10,
            'name' => 'General Admission',
            'price' => 750,
            'capacity' => 100,
        ]);

        DB::table('organizer_reviews')->insert([
            ['organizer_id' => 7, 'rating' => 5, 'is_visible' => true],
            ['organizer_id' => 7, 'rating' => 1, 'is_visible' => false],
        ]);
    }

    public function test_guests_can_browse_real_organizer_profiles(): void
    {
        $this->getJson('/api/organizers')
            ->assertOk()
            ->assertJsonPath('data.0.id', 7)
            ->assertJsonPath('data.0.slug', 'reyes-events-7')
            ->assertJsonPath('data.0.portfolio_events_count', 1)
            ->assertJsonPath('data.0.average_rating', 5)
            ->assertJsonMissing(['email' => 'private@example.test'])
            ->assertJsonMissing(['phone' => '+639171234567']);
    }

    public function test_profile_only_exposes_completed_events_and_visible_reviews(): void
    {
        $this->getJson('/api/organizers/reyes-events-7')
            ->assertOk()
            ->assertJsonPath('data.name', 'Reyes Events')
            ->assertJsonPath('data.events.0.title', 'Garden Wedding')
            ->assertJsonPath('data.events.0.venue', 'Cebu Garden')
            ->assertJsonCount(1, 'data.events')
            ->assertJsonMissing(['title' => 'Private Future Event'])
            ->assertJsonPath('data.reviews_count', 1);
    }

    public function test_unknown_and_invalid_identifiers_return_not_found(): void
    {
        $this->getJson('/api/organizers/not-an-organizer')->assertNotFound();
        $this->getJson('/api/organizers/missing-999')->assertNotFound();
    }

    public function test_guests_can_browse_public_events_without_exposing_private_events(): void
    {
        $this->getJson('/api/marketplace/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Garden Wedding')
            ->assertJsonPath('data.0.organizer.name', 'Reyes Events')
            ->assertJsonPath('data.0.ticket_types.0.name', 'General Admission')
            ->assertJsonPath('data.0.registration_path', '/event-registration/garden-registration')
            ->assertJsonMissing(['name' => 'Private Future Event'])
            ->assertJsonMissing(['public_registration_token' => 'garden-registration']);
    }

    public function test_event_management_uses_the_organizer_profile_owner(): void
    {
        $user = User::query()->findOrFail(1);

        $this->assertSame(2, Event::query()->managedBy($user)->count());
        $this->assertTrue(Event::query()->findOrFail(10)->isManagedBy($user));
    }
}
