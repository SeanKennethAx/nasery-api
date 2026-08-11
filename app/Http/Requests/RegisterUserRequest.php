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
        $phone = $this->input('phone');

        if (! is_string($phone) || $phone === '') {
            return;
        }

        $this->merge([
            'phone' => $this->normalizePhone($phone),
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        // Remove common phone formatting characters.
        $phone = preg_replace('/[\s\-\(\)]+/', '', $phone);

        // +639123456789
        if (preg_match('/^\+639\d{9}$/', $phone)) {
            return $phone;
        }

        // 639123456789
        if (preg_match('/^639\d{9}$/', $phone)) {
            return '+' . $phone;
        }

        // 09123456789
        if (preg_match('/^09\d{9}$/', $phone)) {
            return '+63' . substr($phone, 1);
        }

        // 9123456789
        if (preg_match('/^9\d{9}$/', $phone)) {
            return '+63' . $phone;
        }

        // Leave invalid formats unchanged so validation can reject them.
        return $phone;
    }

    public function rules(): array
    {
        return [
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
                'nullable',
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
            'phone.required' =>
                'The phone number field is required.',

            'phone.regex' =>
                'Please enter a valid Philippine mobile number.',

            'phone.unique' =>
                'This phone number is already registered.',
        ];
    }
}
