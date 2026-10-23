<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppReportTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(User $user, string $name)
    {
        $template = $user->whatsappTemplates()->create(['name' => 'promo', 'display_name' => '', 'language' => 'id', 'header_type' => 'NONE', 'body' => 'Hello']);
        $broadcast = $user->whatsappBroadcasts()->create(['name' => $name, 'whatsapp_template_id' => $template->id, 'recipients' => ['6281234567890', '6281234567891'], 'recipient_count' => 2]);
        $broadcast->approvedRecipients()->create(['msisdn' => '6281234567890', 'variables' => []])->forceFill(['status' => 'sent', 'attempts' => 1, 'sent_at' => now()])->save();
        $broadcast->approvedRecipients()->create(['msisdn' => '6281234567891', 'variables' => []])->forceFill(['status' => 'failed', 'attempts' => 2, 'failure_reason' => '=FORMULA()', 'failed_at' => now()])->save();

        return $broadcast;
    }

    public function test_user_reports_are_scoped_and_other_owners_cannot_be_read_or_exported(): void
    {
        $owner = User::factory()->create();
        $own = $this->campaign($owner, 'Own campaign');
        $other = $this->campaign(User::factory()->create(), 'Private campaign');
        $this->actingAs($owner)->get(route('user.whatsapp.reports.index', ['user_id' => $other->user_id]))
            ->assertOk()->assertSee('Own campaign')->assertDontSee('Private campaign')->assertViewHas('total', 1)
            ->assertViewHas('counts', fn ($counts) => $counts->get('sent') === 1 && $counts->get('failed') === 1);
        $this->get(route('user.whatsapp.reports.show', $own))->assertOk()->assertSee('6281234567890');
        $this->get(route('user.whatsapp.reports.show', $other))->assertForbidden();
        $this->get(route('user.whatsapp.reports.export', $other))->assertForbidden();
        $this->get(route('admin.whatsapp.reports.index'))->assertForbidden();
    }

    public function test_admin_can_filter_all_users_and_export_filtered_results_safely(): void
    {
        $owner = User::factory()->create();
        $broadcast = $this->campaign($owner, 'Selected campaign');
        $this->campaign(User::factory()->create(), 'Other campaign');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->get(route('admin.whatsapp.reports.index'))->assertOk()->assertViewHas('total', 2);
        $this->get(route('admin.whatsapp.reports.index', ['user_id' => $owner->id, 'from' => now()->timezone('Asia/Jakarta')->format('Y-m-d'), 'to' => now()->timezone('Asia/Jakarta')->format('Y-m-d')]))->assertOk()->assertViewHas('total', 1)->assertDontSee('Other campaign');
        $this->get(route('admin.whatsapp.reports.show', ['broadcast' => $broadcast, 'status' => 'failed']))->assertOk()->assertSee('6281234567891')->assertDontSee('6281234567890');
        $response = $this->get(route('admin.whatsapp.reports.export', ['broadcast' => $broadcast, 'status' => 'failed', 'msisdn' => '7891']))->assertOk()->assertDownload('report-whatsapp-'.$broadcast->id.'.csv');
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=FORMULA()", $csv);
        $this->assertStringContainsString('6281234567891', $csv);
        $this->assertStringNotContainsString('6281234567890', $csv);
    }

    public function test_empty_reports_validation_and_guest_access(): void
    {
        $this->get(route('user.whatsapp.reports.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('user.whatsapp.reports.index'))->assertOk()->assertViewHas('total', 0);
        $this->get(route('user.whatsapp.reports.index', ['status' => 'invalid']))->assertSessionHasErrors('status');
        $this->get(route('user.whatsapp.reports.index', ['from' => '2026-10-22', 'to' => '2026-10-01']))->assertSessionHasErrors('to');
        $this->get(route('user.whatsapp.reports.index', ['to' => '2026-10-22']))->assertOk();
    }

    public function test_report_dates_and_exports_use_jakarta_across_utc_midnight(): void
    {
        $owner = User::factory()->create();
        $broadcast = $this->campaign($owner, 'Jakarta midnight');
        $broadcast->forceFill(['created_at' => '2026-10-21 18:30:00'])->save();
        $broadcast->approvedRecipients()->where('status', 'sent')->update(['sent_at' => '2026-10-21 18:30:00']);
        $this->actingAs($owner)->get(route('user.whatsapp.reports.index', ['from' => '2026-10-22', 'to' => '2026-10-22']))
            ->assertOk()->assertViewHas('total', 1)->assertSee('22/10/2026');
        $this->get(route('user.whatsapp.reports.index', ['from' => '2026-10-21', 'to' => '2026-10-21']))
            ->assertOk()->assertViewHas('total', 0);
        $this->get(route('user.whatsapp.reports.show', $broadcast))->assertOk()->assertSee('22 Oct 2026, 01:30:00')->assertSee('Time in UTC+7');
        $csv = $this->get(route('user.whatsapp.reports.export', $broadcast))->streamedContent();
        $this->assertStringContainsString('2026-10-22 01:30:00', $csv);
        $this->assertStringContainsString('Sent (UTC+7)', $csv);
    }

    public function test_sent_recipients_hide_old_failures_in_page_and_downloads(): void
    {
        $owner = User::factory()->create();
        $broadcast = $this->campaign($owner, 'Successful retry');
        $broadcast->approvedRecipients()->update(['status' => 'sent', 'sent_at' => now(), 'failed_at' => now()->subHour(), 'failure_reason' => 'OLD FAILURE']);
        $this->actingAs($owner)->get(route('user.whatsapp.reports.show', $broadcast))
            ->assertOk()->assertSee('Message sent')->assertDontSee('OLD FAILURE')->assertDontSee('report-stat--failed', false);
        $csv = $this->get(route('user.whatsapp.reports.export', $broadcast))->assertOk()->streamedContent();
        $this->assertStringNotContainsString('OLD FAILURE', $csv);
        $response = $this->get(route('user.whatsapp.reports.export', ['broadcast' => $broadcast, 'format' => 'xlsx', 'msisdn' => '7890']))
            ->assertOk()->assertDownload('report-whatsapp-'.$broadcast->id.'.xlsx');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($path));
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertStringContainsString('6281234567890', $sheet);
            $this->assertStringNotContainsString('6281234567891', $sheet);
            $this->assertStringNotContainsString('OLD FAILURE', $sheet);
            $this->assertNotFalse(simplexml_load_string($sheet));
            $zip->close();
        } finally {
            unlink($path);
        }
        $this->actingAs(User::factory()->create())->get(route('user.whatsapp.reports.export', ['broadcast' => $broadcast, 'format' => 'xlsx']))->assertForbidden();
    }

    public function test_excel_preserves_text_and_failed_details_without_formulas(): void
    {
        $owner = User::factory()->create();
        $broadcast = $this->campaign($owner, 'Failed report');
        $response = $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.whatsapp.reports.export', ['broadcast' => $broadcast, 'format' => 'xlsx', 'status' => 'failed']))->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new \ZipArchive;
            $zip->open($path);
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertStringContainsString('=FORMULA()', $sheet);
            $this->assertStringContainsString('t="inlineStr"', $sheet);
            $this->assertStringNotContainsString('<f>', $sheet);
            $this->assertStringNotContainsString('6281234567890', $sheet);
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $this->assertNotFalse(simplexml_load_string($zip->getFromIndex($i)));
            }
            $zip->close();
        } finally {
            unlink($path);
        }
    }
}
