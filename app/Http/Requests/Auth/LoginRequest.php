<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'terminal_token' => ['required', 'string'], // e.g., "X-POS Terminal 01", "ALmax Web App"
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'terminal_token.required' => 'A unique device Token is required to issue an offline-capable client token.',
        ];
    }
}
