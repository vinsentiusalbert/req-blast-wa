<?php

namespace App\Console\Commands;

use App\Models\WhatsappTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PublishTemplateImages extends Command
{
    protected $signature = 'whatsapp:publish-template-images';

    protected $description = 'Copy existing template images from the private local disk to the public disk';

    public function handle(): int
    {
        $private = Storage::disk('local');
        $public = Storage::disk('public');
        $copied = 0;
        $existing = 0;
        $missing = 0;

        foreach (WhatsappTemplate::query()->where('header_type', 'IMAGE')->whereNotNull('asset_path')->lazyById() as $template) {
            $path = $template->asset_path;
            // Only publish the known upload directory, never arbitrary database paths.
            if (! preg_match('~\Awhatsapp-templates/[0-9]+/[a-zA-Z0-9._-]+\.(?:jpg|jpeg|png|webp)\z~i', $path)) {
                $this->warn('Template '.$template->id.': skipped a non-upload asset path.');

                continue;
            }
            if ($public->exists($path)) {
                $existing++;

                continue;
            }
            if (! $private->exists($path)) {
                $missing++;
                $this->error('Template '.$template->id.': image is missing from both disks. Restore or upload the image again.');

                continue;
            }
            $stream = null;
            try {
                $stream = $private->readStream($path);
                if (! is_resource($stream) || ! $public->put($path, $stream)) {
                    throw new RuntimeException('Image copy failed.');
                }
                $copied++;
            } catch (Throwable) {
                $public->delete($path);
                $missing++;
                $this->error('Template '.$template->id.': could not copy the image. Check storage permissions.');
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }
        $this->info("Copied: {$copied}; already public: {$existing}; missing/failed: {$missing}.");
        $this->info('Private originals were preserved. Run php artisan storage:link to expose the public disk at /storage.');

        return $missing > 0 ? self::FAILURE : self::SUCCESS;
    }
}
