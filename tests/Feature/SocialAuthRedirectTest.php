<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SocialAuthRedirectTest extends TestCase
{
    public function test_google_registration_preserves_registration_mode_and_role(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost:8000/api/auth/social/google/callback');

        $response = $this->get('/api/auth/social/google/redirect?role=client&mode=register');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertArrayHasKey('state', $query);
        $this->assertSame([
            'provider' => 'google',
            'role' => 'client',
            'mode' => 'register',
        ], Cache::get('social-auth-state:'.$query['state']));
    }

    public function test_microsoft_registration_preserves_registration_mode_and_role(): void
    {
        config()->set('services.microsoft.client_id', 'microsoft-client-id');
        config()->set('services.microsoft.client_secret', 'microsoft-client-secret');
        config()->set('services.microsoft.redirect', 'http://localhost:8000/api/auth/social/microsoft/callback');
        config()->set('services.microsoft.tenant', 'common');

        $response = $this->get('/api/auth/social/microsoft/redirect?role=organizer&mode=register');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://login.microsoftonline.com/common/oauth2/v2.0/authorize', $location);
        $this->assertArrayHasKey('state', $query);
        $this->assertSame([
            'provider' => 'microsoft',
            'role' => 'organizer',
            'mode' => 'register',
        ], Cache::get('social-auth-state:'.$query['state']));
    }
}
