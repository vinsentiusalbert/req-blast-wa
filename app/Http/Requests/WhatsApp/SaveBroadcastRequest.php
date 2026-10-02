<?php

namespace App\Http\Requests\WhatsApp;

use App\Models\User;
use App\Models\WhatsappTemplate;
use App\Services\WhatsApp\RecipientFileParser;
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
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
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
        ];
    }
}
