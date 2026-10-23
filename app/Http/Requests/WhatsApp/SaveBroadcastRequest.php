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
                $validator->errors()->add('schedule_time', 'The broadcast date and time cannot be in the past (UTC+7).');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'schedule_date.required_with' => 'Select a broadcast date.',
            'schedule_time.required_with' => 'Select a broadcast time.',
            'schedule_date.date_format' => 'The broadcast date is invalid.',
            'schedule_time.date_format' => 'The broadcast time is invalid.',
            'name.required' => 'The broadcast name is required.',
            'name.max' => 'The broadcast name may not exceed 150 characters.',
            'whatsapp_template_id.required' => 'Select a template first.',
            'whatsapp_template_id.exists' => 'Select one of your approved templates.',
            'recipients.required' => 'Enter at least one recipient number.',
        ];
    }
}
