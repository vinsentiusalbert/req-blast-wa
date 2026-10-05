<?php

namespace App\Http\Requests\WhatsApp;

use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappTemplate;
use App\Services\WhatsApp\RecipientFileParser;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class SaveBroadcastRequest extends FormRequest
{
    private string $normalizedRecipients = '';

    private array $recipientRows = [];

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
            'recipients' => ['prohibited'],
            'recipient_file' => ['bail', $this->route('broadcast') ? 'nullable' : 'required', 'file', 'extensions:csv', 'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel', 'max:2048'],
            'send_date' => ['nullable', 'required_with:send_time,end_date,end_time', 'date_format:Y-m-d'],
            'send_time' => ['nullable', 'required_with:send_date,end_date,end_time', 'date_format:H:i'],
            'end_date' => ['nullable', 'required_with:send_date,send_time,end_time', 'date_format:Y-m-d'],
            'end_time' => ['nullable', 'required_with:send_date,send_time,end_date', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->filled('send_date')) {
                $start = CarbonImmutable::createFromFormat('!Y-m-d H:i', $this->input('send_date').' '.$this->input('send_time'), WhatsappBroadcast::SENDING_TIMEZONE);
                $end = CarbonImmutable::createFromFormat('!Y-m-d H:i', $this->input('end_date').' '.$this->input('end_time'), WhatsappBroadcast::SENDING_TIMEZONE);
                $broadcast = $this->route('broadcast');
                $unchanged = $broadcast?->sending_starts_at?->equalTo($start) && $broadcast?->sending_ends_at?->equalTo($end);
                if (! $unchanged && $start->lessThanOrEqualTo(now())) {
                    $validator->errors()->add('send_time', 'Tanggal dan jam mulai kirim harus di masa depan (UTC+7).');
                }
                if ($end->lessThanOrEqualTo($start)) {
                    $validator->errors()->add('end_time', 'Batas akhir pengiriman harus setelah tanggal dan jam mulai.');
                }
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
            }
            if (! $this->hasFile('recipient_file')) {
                $this->normalizedRecipients = implode("\n", $this->route('broadcast')->recipients);

                return;
            }
            try {
                $this->recipientRows = app(RecipientFileParser::class)->readRows($this->file('recipient_file'));
                $numbers = array_column($this->recipientRows, 'phone_number');
                $this->normalizedRecipients = implode("\n", $numbers);
            } catch (InvalidArgumentException $exception) {
                $validator->errors()->add('recipient_file', $exception->getMessage());
            }
        }];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();
        $data['recipients'] = $this->normalizedRecipients;
        $data['recipient_rows'] = $this->hasFile('recipient_file') ? $this->recipientRows : null;

        return data_get($data, $key, $default);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama broadcast wajib diisi.',
            'name.max' => 'Nama broadcast maksimal 150 karakter.',
            'whatsapp_template_id.required' => 'Pilih template terlebih dahulu.',
            'whatsapp_template_id.exists' => 'Pilih template milik Anda yang sudah disetujui admin.',
            'recipients.prohibited' => 'Penerima hanya dapat dimasukkan melalui file CSV.',
            'recipient_file.required' => 'Unggah file CSV penerima.',
            'recipient_file.extensions' => 'Gunakan file CSV.',
            'recipient_file.mimetypes' => 'File harus berupa teks CSV.',
            'recipient_file.max' => 'Ukuran file maksimal 2 MB.',
            'send_date.required_with' => 'Lengkapi tanggal mulai kirim.',
            'send_time.required_with' => 'Lengkapi jam mulai kirim.',
            'end_date.required_with' => 'Lengkapi tanggal batas akhir.',
            'end_time.required_with' => 'Lengkapi jam batas akhir.',
            'send_date.date_format' => 'Tanggal mulai kirim tidak valid.',
            'end_date.date_format' => 'Tanggal batas akhir tidak valid.',
            'send_time.date_format' => 'Jam mulai kirim harus berformat HH:mm.',
            'end_time.date_format' => 'Jam batas akhir harus berformat HH:mm.',
        ];
    }
}
