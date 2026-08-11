<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        /*
         * Normalize email.
         */
        if ($this->has('email')) {
            $email = trim(
                (string) $this->input('email')
            );

            $this->merge([
                'email' => $email !== ''
                    ? strtolower($email)
                    : null,
            ]);
        }

        /*
         * Normalize phone.
         */
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => $this->normalizePhone(
                    (string) $this->input('phone')
                ),
            ]);
        }
    }

    private function normalizePhone(
        string $phone
    ): string {
        $phone = trim($phone);

        $phone = preg_replace(
            '/[\s\-\(\)]+/',
            '',
            $phone
        );

        // +639123456789
        if (
            preg_match(
                '/^\+639\d{9}$/',
                $phone
            )
        ) {
            return $phone;
        }

        // 639123456789
        if (
            preg_match(
                '/^639\d{9}$/',
                $phone
            )
        ) {
            return '+' . $phone;
        }

        // 09123456789
        if (
            preg_match(
                '/^09\d{9}$/',
                $phone
            )
        ) {
            return '+63' . substr(
                $phone,
                1
            );
        }

        // 9123456789
        if (
            preg_match(
                '/^9\d{9}$/',
                $phone
            )
        ) {
            return '+63' . $phone;
        }

        return $phone;
    }

    public function rules(): array
    {
        return [
            'register_method' => [
                'required',
                Rule::in(['email', 'phone']),
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

            'email' => [
                'exclude_unless:register_method,email',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'phone' => [
                'exclude_unless:register_method,phone',
                'required',
                'string',
                'regex:/^\+639\d{9}$/',
                Rule::unique('users', 'phone'),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],

            'role' => [
                'required',
                Rule::in([
                    'organizer',
                    'client',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'register_method.required' =>
            'Please select a registration method.',

            'register_method.in' =>
            'The selected registration method is invalid.',

            'email.required_if' =>
            'The email address field is required.',

            'email.email' =>
            'Please enter a valid email address.',

            'email.unique' =>
            'This email address is already registered.',

            'phone.required_if' =>
            'The phone number field is required.',

            'phone.regex' =>
            'Please enter a valid Philippine mobile number.',

            'phone.unique' =>
            'This phone number is already registered.',
        ];
    }
}
