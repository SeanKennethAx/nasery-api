<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook', 'microsoft'];

    public function redirect(Request $request, string $provider): RedirectResponse|JsonResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            return response()->json(['message' => 'Unsupported social login provider.'], 404);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:client,organizer'],
            'mode' => ['required', 'in:login,register'],
        ]);
        if (! config("services.$provider.client_id")) {
            $page = $validated['mode'] === 'register' ? 'register' : 'login';
            $message = ucfirst($provider).' login is not configured yet.';

            return redirect()->away(rtrim((string) config('app.frontend_url'), '/')."/$page?social_error=".urlencode($message));
        }

        $state = Str::random(64);
        Cache::put("social-auth-state:$state", [
            'provider' => $provider,
            'role' => $validated['role'],
            'mode' => $validated['mode'],
        ], now()->addMinutes(10));

        $driver = Socialite::driver($provider)->stateless()->with(['state' => $state]);

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');
        $attempt = null;

        try {
            $state = (string) $request->input('state');
            $attempt = Cache::pull("social-auth-state:$state");
            if (! $attempt || ($attempt['provider'] ?? null) !== $provider) {
                throw new \RuntimeException('The social login request expired. Please try again.');
            }

            $driver = Socialite::driver($provider)->stateless();
            $profile = $driver->user();
            $email = strtolower(trim((string) $profile->getEmail()));
            if (! $email) {
                throw new \RuntimeException('Your social account did not provide an email address.');
            }

            $user = DB::transaction(function () use ($provider, $profile, $email, $attempt) {
                $account = SocialAccount::query()
                    ->where('provider', $provider)
                    ->where('provider_user_id', (string) $profile->getId())
                    ->first();
                $user = $account ? User::findOrFail($account->user_id) : User::where('email', $email)->first();

                if (! $user) {
                    if (($attempt['mode'] ?? 'login') !== 'register') {
                        throw new \RuntimeException('No NaSeRy account is linked to this social account. Create an account first.');
                    }
                    $parts = preg_split('/\s+/', trim((string) $profile->getName())) ?: [];
                    $user = User::create([
                        'firstname' => array_shift($parts) ?: 'NaSeRy',
                        'lastname' => implode(' ', $parts) ?: 'User',
                        'email' => $email,
                        'email_verified_at' => now(),
                        'password' => Str::random(64),
                        'role' => $attempt['role'],
                    ]);
                    $repository = app(UserRepository::class);
                    $user->role === 'organizer' ? $repository->createOrganizer($user->id) : $repository->createClient($user->id);
                }

                SocialAccount::firstOrCreate([
                    'provider' => $provider,
                    'provider_user_id' => (string) $profile->getId(),
                ], ['user_id' => $user->id]);
                $user->forceFill(['email_verified_at' => $user->email_verified_at ?: now()])->save();

                return $user;
            });

            $code = Str::random(64);
            Cache::put("social-auth-code:$code", $user->id, now()->addMinutes(2));

            return redirect()->away("$frontend/auth/social/callback?code=".urlencode($code));
        } catch (Throwable $error) {
            report($error);

            $page = (($attempt['mode'] ?? 'login') === 'register') ? 'register' : 'login';

            return redirect()->away("$frontend/$page?social_error=".urlencode($error->getMessage()));
        }
    }

    public function exchange(Request $request): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'size:64']]);
        $userId = Cache::pull('social-auth-code:'.$validated['code']);
        abort_unless($userId, 422, 'This social login has expired. Please try again.');
        $user = User::with(['client', 'organizer', 'teamMember'])->findOrFail($userId);

        return response()->json([
            'message' => 'Social login successful.',
            'token' => $user->createToken('social-auth-token')->plainTextToken,
            'user' => $user,
        ]);
    }
}
