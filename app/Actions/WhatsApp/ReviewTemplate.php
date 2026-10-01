<?php

namespace App\Actions\WhatsApp;

use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewTemplate
{
    public function handle(User $reviewer, WhatsappTemplate $template, array $data): void
    {
        DB::transaction(function () use ($reviewer, $template, $data) {
            $current = WhatsappTemplate::query()->lockForUpdate()->findOrFail($template->id);
            if ($current->revision !== (int) $data['revision'] || $current->approval_status !== WhatsappTemplate::PENDING) {
                throw ValidationException::withMessages([
                    'decision' => 'Template telah berubah atau sudah ditinjau. Muat ulang halaman sebelum memberikan keputusan.',
                ]);
            }

            $current->approval_status = $data['decision'];
            $current->reviewed_by = $reviewer->id;
            $current->reviewed_at = now();
            $current->review_note = $data['review_note'] ?? null;
            $current->save();
        });
    }
}
