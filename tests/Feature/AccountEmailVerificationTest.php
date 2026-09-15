<?php

namespace Tests\Feature;

use App\Http\Controllers\AccountEmailVerificationController;
use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountEmailVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('clients');
        Schema::dropIfExists('organizers');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('email_verifications');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('address')->nullable();
            $table->string('password');
            $table->string('role');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('event_token');
            $table->string('code_hash');
            $table->string('verification_token')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->timestamps();
        });

        Schema::create('organizers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('location')->nullable();
            $table->string('google_place_id')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('service_radius_km')->default(25);
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->timestamps();
        });
    }

    public function test_registration_code_is_sent_for_an_available_email(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/email-verification/send', ['email' => 'client@example.com'])
            ->assertOk()
            ->assertJsonPath('data.expires_in', 600);

        Mail::assertSent(EmailVerificationCodeMail::class, fn ($mail) => $mail->hasTo('client@example.com'));
    }

    public function test_verified_email_token_is_required_and_consumed_during_registration(): void
    {
        $verification = EmailVerification::create([
            'email' => 'client@example.com',
            'event_token' => AccountEmailVerificationController::SCOPE,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now()->subMinute(),
        ]);

        $token = $this->postJson('/api/auth/email-verification/verify', [
            'email' => 'client@example.com',
            'code' => '123456',
        ])->assertOk()->json('data.verification_token');

        $this->postJson('/api/auth/register', [
            'firstname' => 'Test',
            'lastname' => 'Client',
            'email' => 'client@example.com',
            'email_verification_token' => $token,
            'phone' => '+639123456789',
            'address' => 'Davao City',
            'password' => 'password123',
            'role' => 'client',
        ])->assertCreated();

        $this->assertNotNull($verification->fresh()->used_at);
        $this->assertNotNull(User::query()->firstOrFail()->email_verified_at);
    }

    public function test_organizer_can_register_without_an_optional_service_address(): void
    {
        EmailVerification::create([
            'email' => 'organizer@example.com',
            'event_token' => AccountEmailVerificationController::SCOPE,
            'code_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now()->subMinute(),
        ]);

        $token = $this->postJson('/api/auth/email-verification/verify', [
            'email' => 'organizer@example.com',
            'code' => '654321',
        ])->assertOk()->json('data.verification_token');

        $this->postJson('/api/auth/register', [
            'firstname' => 'Test',
            'lastname' => 'Organizer',
            'email' => 'organizer@example.com',
            'email_verification_token' => $token,
            'phone' => '+639123456780',
            'address' => 'Davao City',
            'password' => 'password123',
            'role' => 'organizer',
        ])->assertCreated();

        $this->assertDatabaseHas('organizers', [
            'user_id' => User::query()->firstOrFail()->id,
            'location' => null,
        ]);
    }
}
