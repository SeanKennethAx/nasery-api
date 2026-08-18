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


    public function login(
        Request $request
    ): JsonResponse {
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

        return response()->json(
            $result
        );
    }


    public function register(
        Request $request
    ): JsonResponse {
        $validated = $request->validate(
            [
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

                'email' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                ],

                'phone' => [
                    'required',
                    'string',
                    'regex:/^\+639\d{9}$/',
                    'unique:users,phone',
                ],

                'address' => [
                    'required',
                    'string',
                    'max:500',
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
            ],
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

                'address.required' =>
                'The address field is required.',
            ]
        );

        $user = $this->userService->register(
            $validated
        );

        /*
         * Load the related profile created
         * during registration.
         */
        $user->load([
            'client',
            'organizer',
        ]);

        return response()->json([
            'message' =>
            'Account created successfully.',

            'data' => [
                'id' =>
                $user->id,

                'firstname' =>
                $user->firstname,

                'middlename' =>
                $user->middlename,

                'lastname' =>
                $user->lastname,

                'email' =>
                $user->email,

                'phone' =>
                $user->phone,

                'address' =>
                $user->address,

                'role' =>
                $user->role,

                /*
                 * If role = client,
                 * this contains clients.id.
                 */
                'client_id' =>
                $user->client?->id,

                /*
                 * If role = organizer,
                 * this contains organizers.id.
                 */
                'organizer_id' =>
                $user->organizer?->id,
            ],
        ], 201);
    }


    public function me(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        /*
         * Load client / organizer profile
         * so their profile IDs are available.
         */
        $user->load([
            'client',
            'organizer',
        ]);

        return response()->json([
            'user' => [
                'id' =>
                $user->id,

                'firstname' =>
                $user->firstname,

                'middlename' =>
                $user->middlename,

                'lastname' =>
                $user->lastname,

                'email' =>
                $user->email,

                'phone' =>
                $user->phone,

                'address' =>
                $user->address,

                'role' =>
                $user->role,

                'client_id' =>
                $user->client?->id,

                'organizer_id' =>
                $user->organizer?->id,
            ],
        ]);
    }
}
