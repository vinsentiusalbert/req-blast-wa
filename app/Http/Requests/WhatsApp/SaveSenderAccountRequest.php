<?php

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSenderAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'sender' => ['required', 'string', 'regex:/\A62[0-9]{6,13}\z/', Rule::unique('whatsapp_sender_accounts')->ignore($this->route('sender'))],
            'api_base_url' => ['bail', 'required', 'string', 'max:2048', 'url:http,https', function ($attribute, $value, $fail) {
                $parts = parse_url($value);
                if ($parts === false || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
                    $fail('Use a gateway base URL without credentials, query parameters, or fragments.');
                }
            }],
            'api_key' => [$this->route('sender') ? 'nullable' : 'required', 'string', 'max:4096'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'min_delay_seconds' => ['required', 'integer', 'min:1', 'max:86400'],
            'max_delay_seconds' => ['required', 'integer', 'gte:min_delay_seconds', 'max:86400'],
            'daily_limit' => ['required', 'integer', 'min:1', 'max:100000'],
            'failure_cooldown_seconds' => ['required', 'integer', 'min:60', 'max:604800'],
            'failure_threshold' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
