<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IntegrationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'scope' => 'required|in:user,system',
            'service' => 'required|string|max:255',
            'service_type' => 'required|string|max:255',
            'auth_type' => 'required|string|max:255',
            'server_url' => 'nullable|url|max:255',
            'api_key' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'access_token' => 'nullable|string|max:255',
            'refresh_token' => 'nullable|string|max:255',
            'token_expires_at' => 'nullable|date',
        ];
    }
} 