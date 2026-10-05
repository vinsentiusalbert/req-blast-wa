<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppDeliveryReportTest extends TestCase
{
    use RefreshDatabase;

    private function broadcast(User $user): WhatsappBroadcast
    {
        $template = $user->whatsappTemplates()->create([
            'name' => 'delivery_report', 'language' => 'id', 'display_name' => 'Toko',
            'header_type' => 'NONE', 'body' => 'Halo', 'buttons' => [],
        ]);
        $template->forceFill(['approval_status' => WhatsappTemplate::APPROVED])->save();
        $broadcast = $user->whatsappBroadcasts()->create([
            'name' => 'DLR campaign', 'whatsapp_template_id' => $template->id,
            'recipients' => [], 'recipient_count' => 33,
        ]);
        $statuses = ['pending', 'sent', 'delivered', 'read', 'success', 'failed', 'failed', 'processing'];
        foreach (range(1, 33) as $index) {
            $status = $statuses[$index - 1] ?? 'pending';
            $entry = $broadcast->recipientEntries()->create([
                'user_id' => $user->id, 'phone_number' => '6281234'.str_pad($index, 5, '0', STR_PAD_LEFT),
            ]);
            $entry->forceFill([
                'delivery_status' => $status,
                'sent_at' => $status === 'sent' ? '2026-10-02 03:00:00' : null,
                'last_error' => match ($index) {
                    6 => '<script>Nomor tidak terdaftar</script>',
                    7 => null,
                    default => 'Stale error must be hidden',
                },
            ])->save();
        }

        return $broadcast;
    }

    public function test_user_and_admin_reports_show_counts_statuses_errors_and_filtered_pages(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $broadcast = $this->broadcast($user);
        foreach ([
            [$user, 'user.whatsapp.broadcasts.show'],
            [User::factory()->create(['role' => User::ROLE_ADMIN]), 'admin.whatsapp.campaigns.show'],
        ] as [$viewer, $route]) {
            $this->actingAs($viewer);
            $url = route($route, $broadcast);
            $this->get($url)->assertOk()
                ->assertViewHas('dlrCounts', ['pending' => 27, 'sent' => 1, 'success' => 3, 'failed' => 2])
                ->assertViewHas('dlrRows', fn ($rows) => $rows->count() === 25 && $rows->total() === 33)
                ->assertSee('02 Oct 2026, 10:00:00')
                ->assertSee('&lt;script&gt;Nomor tidak terdaftar&lt;/script&gt;', false)
                ->assertDontSee('<script>Nomor tidak terdaftar</script>', false)
                ->assertDontSee('Stale error must be hidden')->assertSee('Alasan kegagalan belum tersedia.');
            $this->get($url.'?dlr_status=success')->assertOk()
                ->assertViewHas('dlrRows', fn ($rows) => $rows->total() === 3 && $rows->every(fn ($row) => $row->reportStatus() === 'success'))
                ->assertViewHas('dlrCounts', fn ($counts) => $counts['failed'] === 2);
            $this->get($url.'?dlr_status=pending&dlr_page=2')->assertOk()
                ->assertViewHas('dlrRows', fn ($rows) => $rows->count() === 2 && $rows->total() === 27);
            $this->get($url.'?dlr_search=08123400006&dlr_status=failed')->assertOk()
                ->assertViewHas('dlrRows', fn ($rows) => $rows->total() === 1 && $rows->first()->phone_number === '628123400006');
            $this->get($url.'?dlr_search=999999')->assertOk()->assertSee('Tidak ada penerima yang cocok');
            $this->get($url.'?dlr_status=bogus')->assertSessionHasErrors('dlr_status');
            $this->get($url.'?dlr_search[]=123')->assertSessionHasErrors('dlr_search');
            $this->get($url.'?dlr_page=0')->assertSessionHasErrors('dlr_page');
        }
    }

    public function test_csv_exports_all_filtered_rows_and_prevents_spreadsheet_formulas(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $broadcast = $this->broadcast($user);
        $broadcast->recipientEntries()->where('delivery_status', 'failed')->first()->forceFill(['last_error' => '=HYPERLINK("bad")'])->save();
        foreach ([
            [$user, 'user.whatsapp.broadcasts.dlr.export'],
            [User::factory()->create(['role' => User::ROLE_ADMIN]), 'admin.whatsapp.campaigns.dlr.export'],
        ] as [$viewer, $route]) {
            $this->actingAs($viewer);
            $url = route($route, $broadcast);
            $response = $this->get($url.'?dlr_page=2')->assertOk()->assertDownload('dlr-broadcast-'.$broadcast->id.'.csv');
            $csv = $response->streamedContent();
            $this->assertStringContainsString('628123400001,Pending', $csv);
            $this->assertStringContainsString('628123400033,Pending', $csv);
            $this->assertStringContainsString('628123400002,Sent,"2026-10-02 10:00:00"', $csv);
            $filtered = $this->get($url.'?dlr_status=failed')->assertOk()->streamedContent();
            $this->assertStringContainsString('628123400006,Failed', $filtered);
            $this->assertStringContainsString('628123400007,Failed', $filtered);
            $this->assertStringNotContainsString('628123400003', $filtered);
            $this->assertStringContainsString("'=HYPERLINK", $filtered);
            $this->assertStringNotContainsString('Stale error must be hidden', $filtered);
        }
    }

    public function test_reports_and_exports_are_scoped_to_campaign_and_authorized_owner(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $other = User::factory()->create(['role' => User::ROLE_USER]);
        $broadcast = $this->broadcast($user);
        $foreign = $this->broadcast($other);
        $foreign->recipientEntries()->where('delivery_status', 'failed')->update(['last_error' => 'Foreign campaign error']);
        $this->actingAs($user)->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertOk()->assertDontSee('Foreign campaign error');
        $csv = $this->get(route('user.whatsapp.broadcasts.dlr.export', $broadcast))->assertOk()->streamedContent();
        $this->assertStringNotContainsString('Foreign campaign error', $csv);
        $this->get(route('user.whatsapp.broadcasts.show', $foreign))->assertForbidden();
        $this->get(route('user.whatsapp.broadcasts.dlr.export', $foreign))->assertForbidden();
        $this->get(route('admin.whatsapp.campaigns.dlr.export', $foreign))->assertForbidden();
        $this->get(route('user.whatsapp.broadcasts.dlr.export', $broadcast).'?dlr_status=invalid')->assertSessionHasErrors('dlr_status');
        $this->get(route('user.whatsapp.broadcasts.dlr.export', $broadcast).'?dlr_search=%25')->assertSessionHasErrors('dlr_search');
        $this->post(route('user.whatsapp.broadcasts.dlr.export', $broadcast))->assertStatus(405);
        $this->assertSame('pending', $broadcast->recipientEntries()->first()->delivery_status);
    }
}
