<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'phone' => ['required', 'regex:/^\+639\d{9}$/'],
        ]);
        $email = strtolower(trim($validated['email']));
        $user = User::where('email', $email)->where('phone', $validated['phone'])->first();
        if (! $user) {
            return response()->json(['message' => 'The email and phone number do not match a registered account.'], 422);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );
        Mail::to($email)->send(new PasswordResetCodeMail($code));

        return response()->json(['message' => 'A 6-digit reset code was sent to your registered email.']);
    }

    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'phone' => ['required', 'regex:/^\+639\d{9}$/'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $email = strtolower(trim($validated['email']));
        $user = User::where('email', $email)->where('phone', $validated['phone'])->first();
        $reset = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (! $user || ! $reset || now()->diffInMinutes($reset->created_at) > 10 || ! Hash::check($validated['code'], $reset->token)) {
            return response()->json(['message' => 'The reset code is invalid or expired.'], 422);
        }

        DB::transaction(function () use ($user, $email, $validated) {
            $user->update(['password' => $validated['password']]);
            $user->tokens()->delete();
            DB::table('password_reset_tokens')->where('email', $email)->delete();
        });

        return response()->json(['message' => 'Your password has been reset successfully.']);
    }
}
