<?php

namespace App\Actions\WhatsApp;

use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SaveTemplate
{
    public function handle(User $user, array $data, ?WhatsappTemplate $template = null): WhatsappTemplate
    {
        $template ??= $user->whatsappTemplates()->make();
        $oldPath = $template->asset_path;
        $newPath = null;

        try {
            if (($data['asset'] ?? null) instanceof UploadedFile) {
                $newPath = $data['asset']->store('whatsapp-templates/'.$user->id, 'local');
                if (! $newPath) {
                    throw new RuntimeException('Gambar template gagal disimpan.');
                }
            }

            $attributes = Arr::only($data, ['name', 'language', 'header_type', 'body']);
            $attributes['footer'] = null;
            // Keep the legacy database column empty for templates without a sender name.
            $attributes['display_name'] = '';
            $attributes['header_text'] = $data['header_type'] === 'TEXT' ? $data['header_text'] : null;
            $attributes['buttons'] = [];

            DB::transaction(function () use ($template, $attributes, $newPath, &$oldPath) {
                // Serialize edits with admin review so approval always applies to one revision.
                $current = $template->exists
                    ? WhatsappTemplate::query()->lockForUpdate()->findOrFail($template->id)
                    : $template;
                $oldPath = $current->asset_path;
                $attributes['asset_path'] = $attributes['header_type'] === 'IMAGE' ? ($newPath ?? $oldPath) : null;
                if ($attributes['header_type'] === 'IMAGE' && ! $attributes['asset_path']) {
                    throw ValidationException::withMessages(['asset' => 'Unggah gambar untuk header template.']);
                }
                $current->fill($attributes);
                $current->approval_status = WhatsappTemplate::PENDING;
                $current->revision = $current->exists ? $current->revision + 1 : 1;
                $current->reviewed_by = null;
                $current->reviewed_at = null;
                $current->review_note = null;
                $current->save();
                $template->setRawAttributes($current->getAttributes(), true);
                $template->exists = true;
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }

        if ($oldPath && $oldPath !== $template->asset_path) {
            Storage::disk('local')->delete($oldPath);
        }

        return $template;
    }
}
