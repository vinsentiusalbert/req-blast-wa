<?php

namespace App\Http\Requests\WhatsApp;

use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('template'));
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([WhatsappTemplate::APPROVED, WhatsappTemplate::REJECTED])],
            'revision' => ['required', 'integer', 'min:1'],
            'review_note' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'review_note.required_if' => 'Alasan penolakan wajib diisi agar user dapat memperbaiki template.',
            'review_note.max' => 'Catatan maksimal 1.000 karakter.',
        ];
    }
}
