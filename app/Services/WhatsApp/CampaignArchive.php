<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappBroadcast;
use Illuminate\Support\Facades\Storage;
use Phar;
use PharData;

class CampaignArchive
{
    public function create(WhatsappBroadcast $broadcast): string
    {
        $base = tempnam(sys_get_temp_dir(), 'wa-campaign-');
        $paths = [$base, $base.'.csv', $base.'.txt', $base.'.report.csv', $base.'.tar'];
        $streams = [];
        try {
            $messages = $streams[] = fopen($base.'.csv', 'wb');
            $numbers = $streams[] = fopen($base.'.txt', 'wb');
            $report = $streams[] = fopen($base.'.report.csv', 'wb');
            fputcsv($messages, ['msisdn', 'message', 'header_text', 'footer'], ',', '"', '');
            fputcsv($report, ['msisdn', 'status', 'failure_reason'], ',', '"', '');
            $template = $broadcast->template;
            foreach ($broadcast->approvedRecipients()->lazyById(500) as $recipient) {
                fputcsv($messages, [$recipient->msisdn, $template->body,
                    $template->header_type === 'TEXT' ? $template->header_text : '',
                    $template->footer ?? ''], ',', '"', '');
                fwrite($numbers, $recipient->msisdn."\n");
                fputcsv($report, [$recipient->msisdn, $recipient->status, $recipient->failure_reason ?? ''], ',', '"', '');
            }
            foreach ($streams as $stream) {
                fclose($stream);
            }
            $streams = [];
            $excel = $paths[] = (new ReportExcel)->create((function () use ($broadcast) {
                yield ['msisdn'];
                foreach ($broadcast->approvedRecipients()->lazyById(500) as $recipient) {
                    yield [$recipient->msisdn];
                }
            })());
            clearstatcache();
            $archive = new PharData($base.'.tar');
            $archive->addFile($base.'.csv', 'messages.csv');
            $archive->addFile($base.'.txt', 'msisdn.txt');
            $archive->addFile($excel, 'msisdn.xlsx');
            $redaction = array_filter([
                $template->header_type === 'TEXT' ? $template->header_text : null,
                $template->body,
                $template->footer,
            ], fn ($text) => $text !== null && $text !== '');
            $archive->addFromString('redaksi.txt', implode("\n\n", $redaction));
            if ($template->header_type === 'IMAGE') {
                $asset = $template->asset_path ?? '';
                abort_unless(preg_match('~\Awhatsapp-templates/[0-9]+/[a-zA-Z0-9._-]+\.(?:jpg|jpeg|png|webp)\z~i', $asset), 409, 'The template image is unavailable. Upload the image again before downloading.');
                $disk = Storage::disk('public')->exists($asset) ? Storage::disk('public') : Storage::disk('local');
                abort_unless($disk->exists($asset), 409, 'The template image is missing. Upload the image again before downloading.');
                $archive->addFromString('image.'.strtolower(pathinfo($asset, PATHINFO_EXTENSION)), $disk->get($asset));
            }
            $archive->addFile($base.'.report.csv', 'report-template.csv');
            $archive->addFromString('template.json', json_encode($template->only([
                'header_type', 'header_text', 'body', 'footer', 'buttons',
            ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $compressed = $archive->compress(Phar::GZ);
            unset($compressed, $archive);

            return $base.'.tar.gz';
        } catch (\Throwable $exception) {
            unset($compressed, $archive);
            if (is_file($base.'.tar.gz')) {
                unlink($base.'.tar.gz');
            }
            throw $exception;
        } finally {
            foreach ($streams as $stream) {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
            foreach ($paths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }
}
