<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => [
                'nullable',
                'email',
                'required_without:phone',
            ],

            'phone' => [
                'nullable',
                'string',
                'required_without:email',
                'regex:/^\+639\d{9}$/',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);

        $result = $this->userService->login(
            $validated
        );

        return response()->json($result);
    }

    public function register(Request $request): JsonResponse
    {
        $method = $request->input('register_method');

        $rules = [
            'register_method' => [
                'required',
                'in:email,phone',
            ],

            'firstname' => [
                'required',
                'string',
                'max:100',
            ],

            'middlename' => [
                'nullable',
                'string',
                'max:100',
            ],

            'lastname' => [
                'required',
                'string',
                'max:100',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],

            'role' => [
                'required',
                'in:organizer,client',
            ],
        ];

        /*
     * EMAIL REGISTRATION
     *
     * Phone is NOT validated at all.
     */
        if ($method === 'email') {
            $rules['email'] = [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ];
        }

        /*
     * PHONE REGISTRATION
     *
     * Email is NOT validated at all.
     */
        if ($method === 'phone') {
            $rules['phone'] = [
                'required',
                'string',
                'regex:/^\+639\d{9}$/',
                'unique:users,phone',
            ];
        }

        $validated = $request->validate(
            $rules,
            [
                'email.required' =>
                'The email address field is required.',

                'email.email' =>
                'Please enter a valid email address.',

                'email.unique' =>
                'This email address is already registered.',

                'phone.required' =>
                'The phone number field is required.',

                'phone.regex' =>
                'Please enter a valid Philippine mobile number.',

                'phone.unique' =>
                'This phone number is already registered.',
            ]
        );

        $user = $this->userService->register(
            $validated
        );

        return response()->json([
            'message' => 'Account created successfully.',

            'data' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'middlename' => $user->middlename,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ], 201);
    }
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'middlename' => $user->middlename,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ]);
    }
}
