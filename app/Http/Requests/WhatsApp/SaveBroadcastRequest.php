<?php

namespace App\Http\Requests\WhatsApp;

use App\Models\User;
use App\Models\WhatsappTemplate;
use App\Rules\RecipientList;
use Carbon\CarbonImmutable;
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
            'schedule_date' => ['nullable', 'required_with:schedule_time', 'date_format:Y-m-d'],
            'schedule_time' => ['nullable', 'required_with:schedule_date', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:150'],
            'whatsapp_template_id' => ['required', 'integer', Rule::exists('whatsapp_templates', 'id')->where('user_id', $this->user()->id)->where('approval_status', WhatsappTemplate::APPROVED)],
            'recipients_csv' => [Rule::requiredIf(! $this->route('broadcast') && ! $this->has('recipients')), 'file', 'extensions:csv'],
            'recipients' => ['bail', 'sometimes', 'required', 'string', new RecipientList],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('schedule_date') || ! $this->filled('schedule_time')) {
                return;
            }
            $scheduled = CarbonImmutable::createFromFormat('!Y-m-d H:i', $this->input('schedule_date').' '.$this->input('schedule_time'), 'Asia/Jakarta');
            if ($scheduled->lessThan(now('Asia/Jakarta')->startOfMinute())) {
                $validator->errors()->add('schedule_time', 'Tanggal dan jam broadcast tidak boleh sebelum waktu sekarang (WIB).');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'schedule_date.required_with' => 'Pilih tanggal broadcast.',
            'schedule_time.required_with' => 'Pilih jam broadcast.',
            'schedule_date.date_format' => 'Tanggal broadcast tidak valid.',
            'schedule_time.date_format' => 'Jam broadcast tidak valid.',
            'name.required' => 'Nama broadcast wajib diisi.',
            'name.max' => 'Nama broadcast maksimal 150 karakter.',
            'whatsapp_template_id.required' => 'Pilih template terlebih dahulu.',
            'whatsapp_template_id.exists' => 'Pilih template milik Anda yang sudah disetujui admin.',
            'recipients.required' => 'Masukkan minimal satu nomor penerima.',
        ];
    }
}
