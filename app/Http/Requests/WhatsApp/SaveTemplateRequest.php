<?php

namespace App\Http\Requests\WhatsApp;

use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTemplateRequest extends FormRequest
{
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
            'required' => ':attribute wajib diisi.',
            'max.string' => ':attribute maksimal :max karakter.',
            'name.regex' => 'Nama template harus diawali huruf kecil dan hanya berisi huruf kecil, angka, serta garis bawah.',
            'name.unique' => 'Anda sudah memiliki template dengan nama ini.',
            'asset.required' => 'Unggah gambar untuk header template.',
            'asset.image' => 'Asset harus berupa gambar.',
            'asset.mimes' => 'Gambar harus berformat JPG, PNG, atau WebP.',
            'asset.max' => 'Ukuran gambar harus di bawah 1 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nama template', 'language' => 'Bahasa',
            'header_text' => 'Teks header', 'body' => 'Isi pesan',
        ];
    }
}
