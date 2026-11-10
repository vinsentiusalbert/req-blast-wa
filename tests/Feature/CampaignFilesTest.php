<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CampaignFilesTest extends TestCase
{
    use RefreshDatabase;

    private function campaign()
    {
        $owner = User::factory()->create();
        $template = $owner->whatsappTemplates()->create(['name' => 'promo', 'display_name' => '', 'language' => 'id', 'header_type' => 'TEXT', 'header_text' => 'Hi {{name}}', 'body' => "Hello {{name}},\nYour offer 😊 🎉 👩🏽‍💻", 'footer' => 'Thank you']);
        $campaign = $owner->whatsappBroadcasts()->create(['name' => 'Campaign', 'whatsapp_template_id' => $template->id, 'recipients' => ['6281234567890', '6281234567891'], 'recipient_count' => 2]);
        $campaign->forceFill(['status' => 'approve'])->save();
        foreach ($campaign->recipients as $number) {
            $campaign->approvedRecipients()->create(['msisdn' => $number, 'variables' => ['name' => 'Budi']]);
        }

        return $campaign;
    }

    private function upload($campaign, string $csv)
    {
        return $this->post(route('admin.whatsapp.reports.upload', $campaign), [
            'report_csv' => UploadedFile::fake()->createWithContent('report.csv', $csv),
        ]);
    }

    public function test_archive_preserves_original_messages_emoji_and_variable_placeholders(): void
    {
        $campaign = $this->campaign();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('admin.whatsapp.broadcasts.show', $campaign))->assertOk()->assertSee('Upload campaign report');
        $response = $this->get(route('admin.whatsapp.broadcasts.download', $campaign))
            ->assertOk()->assertDownload('campaign-'.$campaign->id.'.tar.gz');
        $path = $response->baseResponse->getFile()->getPathname();
        // Open a downloaded copy, independent of Phar's in-process creation cache.
        $download = $path.'.download.tar.gz';
        copy($path, $download);
        try {
            $this->assertStringContainsString("6281234567890\n6281234567891\n", gzdecode(file_get_contents($path)));
            $archive = new \PharData($download);
            $this->assertSame("6281234567890\n6281234567891\n", $archive['msisdn.txt']->getContent());
            $csv = fopen('php://memory', 'r+');
            fwrite($csv, $archive['messages.csv']->getContent());
            rewind($csv);
            $this->assertSame(['msisdn', 'message', 'header_text', 'footer'], fgetcsv($csv, null, ',', '"', ''));
            foreach ($campaign->recipients as $number) {
                $this->assertSame([$number, $campaign->template->body, 'Hi {{name}}', 'Thank you'], fgetcsv($csv, null, ',', '"', ''));
            }
            $this->assertFalse(fgetcsv($csv, null, ',', '"', ''));
            fclose($csv);
            $this->assertStringNotContainsString('Budi', $archive['messages.csv']->getContent());
            $this->assertStringContainsString('msisdn,status,failure_reason', $archive['report-template.csv']->getContent());
            $this->assertSame('Thank you', json_decode($archive['template.json']->getContent(), true)['footer']);
            unset($archive);
        } finally {
            unlink($path);
            unlink($download);
        }
    }

    public function test_upload_updates_only_selected_campaign_and_is_repeatable(): void
    {
        $campaign = $this->campaign();
        $other = $this->campaign();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $csv = "\xEF\xBB\xBFmsisdn,status,failure_reason\n6281234567890,sent,\n6281234567891,failed,Invalid number\n";
        for ($i = 0; $i < 2; $i++) {
            $this->upload($campaign, $csv)->assertSessionHasNoErrors()->assertRedirect(route('admin.whatsapp.reports.show', $campaign));
        }
        $this->assertSame(1, $campaign->approvedRecipients()->where('status', 'sent')->count());
        $this->assertSame('Invalid number', $campaign->approvedRecipients()->where('status', 'failed')->first()->failure_reason);
        $this->assertSame(2, $other->approvedRecipients()->where('status', 'pending')->count());
        $this->assertSame(0, $campaign->approvedRecipients()->sum('attempts'));
        $this->assertNull($campaign->approvedRecipients()->where('status', 'sent')->first()->sent_at);
        $this->actingAs($campaign->user)->get(route('user.whatsapp.reports.show', $campaign))
            ->assertOk()->assertSee('Invalid number')->assertDontSee('Upload campaign report');
    }

    public function test_invalid_uploads_are_atomic_and_campaign_scoped(): void
    {
        $campaign = $this->campaign();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ([
            "msisdn,status,failure_reason\n6281234567890,sent,\n6289999999999,failed,Unknown\n",
            "msisdn,status,failure_reason\n6281234567890,sent,\n6281234567890,failed,Duplicate\n",
            "msisdn,status,failure_reason\n6281234567890,delivered,\n",
            "msisdn,status,failure_reason\n",
            "number,result\n6281234567890,sent\n",
        ] as $csv) {
            $this->upload($campaign, $csv)->assertSessionHasErrors('report_csv');
            $this->assertSame(2, $campaign->approvedRecipients()->where('status', 'pending')->count());
        }
    }

    public function test_guest_users_and_unapproved_campaigns_cannot_use_campaign_files(): void
    {
        $campaign = $this->campaign();
        $csv = "msisdn,status,failure_reason\n6281234567890,sent,\n";
        $this->get(route('admin.whatsapp.broadcasts.download', $campaign))->assertRedirect(route('login'));
        $this->upload($campaign, $csv)->assertRedirect(route('login'));
        $this->actingAs($campaign->user)->get(route('admin.whatsapp.broadcasts.download', $campaign))->assertForbidden();
        $this->upload($campaign, $csv)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $campaign->forceFill(['status' => 'pending'])->save();
        $this->get(route('admin.whatsapp.broadcasts.download', $campaign))->assertStatus(409);
        $this->upload($campaign, $csv)->assertSessionHasErrors('report_csv');
    }
}
