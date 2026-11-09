<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappBroadcast;
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
            $renderer = new BroadcastCsv;
            foreach ($broadcast->approvedRecipients()->lazyById(500) as $recipient) {
                $variables = ['msisdn' => $recipient->msisdn] + ($recipient->variables ?? []);
                fputcsv($messages, [$recipient->msisdn, $renderer->render($template->body, $variables),
                    $template->header_type === 'TEXT' ? $renderer->render($template->header_text, $variables) : '',
                    $template->footer ?? ''], ',', '"', '');
                fwrite($numbers, $recipient->msisdn."\n");
                fputcsv($report, [$recipient->msisdn, $recipient->status, $recipient->failure_reason ?? ''], ',', '"', '');
            }
            foreach ($streams as $stream) {
                fclose($stream);
            }
            $streams = [];
            clearstatcache();
            $archive = new PharData($base.'.tar');
            $archive->addFile($base.'.csv', 'messages.csv');
            $archive->addFile($base.'.txt', 'msisdn.txt');
            $archive->addFile($base.'.report.csv', 'report-template.csv');
            $archive->addFromString('template.json', json_encode($template->only([
                'header_type', 'header_text', 'body', 'footer', 'buttons',
            ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $compressed = $archive->compress(Phar::GZ);
            unset($compressed, $archive);

            return $base.'.tar.gz';
        } catch (\Throwable $exception) {
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
