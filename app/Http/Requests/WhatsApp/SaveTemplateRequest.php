<?php

namespace App\Http\Requests\WhatsApp;

use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTemplateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('header_type') === 'TEXT' && is_string($this->input('header_text'))) {
            $this->merge(['header_text' => WhatsappTemplate::headerContent($this->input('header_text'))]);
        }
    }

    public function authorize(): bool
    {
        $template = $this->route('template');

        return $this->user()?->role === User::ROLE_USER
            && (! $template || $this->user()->can('update', $template));
    }

    public function rules(): array
    {
        $template = $this->route('template');
        $needsImage = $this->input('header_type') === 'IMAGE' && ! $template?->asset_path;

        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/D', Rule::unique('whatsapp_templates')->where('user_id', $this->user()->id)->ignore($template?->id)],
            'language' => ['required', Rule::in(array_keys(WhatsappTemplate::LANGUAGES))],
            'header_type' => ['required', Rule::in(array_keys(WhatsappTemplate::HEADER_TYPES))],
            'header_text' => ['exclude_unless:header_type,TEXT', 'required', 'string', 'max:60'],
            'asset' => ['exclude_unless:header_type,IMAGE', Rule::requiredIf($needsImage), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.((1024 * 1024 - 1) / 1024)],
            'body' => ['required', 'string', 'max:'.WhatsappTemplate::MAX_BODY_LENGTH],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute is required.',
            'max.string' => ':attribute may not exceed :max characters.',
            'name.regex' => 'The template name must start with a lowercase letter and contain only lowercase letters, numbers, and underscores.',
            'name.unique' => 'You already have a template with this name.',
            'asset.required' => 'Upload an image for the template header.',
            'asset.image' => 'The asset must be an image.',
            'asset.mimes' => 'The image must be JPG, PNG, or WebP.',
            'asset.max' => 'The image must be under 1 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Template name', 'language' => 'Language',
            'header_text' => 'Header text', 'body' => 'Message body',
        ];
    }
}
