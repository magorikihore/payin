<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'max:10'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'referral_code' => ['nullable', 'string', 'max:20'],
            // Honeypot: must be empty (bots fill hidden fields)
            'website' => ['nullable', 'max:0'],
            '_form_loaded_at' => ['nullable', 'numeric'],
        ];
    }

    /**
     * Configure the validator instance — add bot protection checks.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Reject if honeypot field has a value
            if (!empty($this->input('website'))) {
                $validator->errors()->add('bot', 'Registration rejected.');
            }

            // Reject if form was submitted too fast (< 3 seconds)
            $loadedAt = $this->input('_form_loaded_at');
            if ($loadedAt) {
                $elapsedSeconds = (now()->timestamp * 1000 - (int) $loadedAt) / 1000;
                if ($elapsedSeconds < 3) {
                    $validator->errors()->add('bot', 'Form submitted too quickly. Please try again.');
                }
            }
        });
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'website.max' => 'Registration rejected.',
        ];
    }
}
