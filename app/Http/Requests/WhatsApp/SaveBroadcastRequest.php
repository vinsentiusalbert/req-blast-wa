<?php

namespace App\Http\Requests\WhatsApp;

use App\Models\User;
use App\Models\WhatsappTemplate;
use App\Rules\RecipientList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveBroadcastRequest extends FormRequest
{
    public function authorize(): bool
    {
        $broadcast = $this->route('broadcast');

        return $this->user()?->role === User::ROLE_USER
            && (! $broadcast || $this->user()->can('update', $broadcast));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'whatsapp_template_id' => ['required', 'integer', Rule::exists('whatsapp_templates', 'id')->where('user_id', $this->user()->id)->where('approval_status', WhatsappTemplate::APPROVED)],
            'recipients_csv' => [Rule::requiredIf(! $this->route('broadcast') && ! $this->has('recipients')), 'file', 'extensions:csv'],
            'recipients' => ['bail', 'sometimes', 'required', 'string', new RecipientList],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama broadcast wajib diisi.',
            'name.max' => 'Nama broadcast maksimal 150 karakter.',
            'whatsapp_template_id.required' => 'Pilih template terlebih dahulu.',
            'whatsapp_template_id.exists' => 'Pilih template milik Anda yang sudah disetujui admin.',
            'recipients.required' => 'Masukkan minimal satu nomor penerima.',
        ];
    }
}
