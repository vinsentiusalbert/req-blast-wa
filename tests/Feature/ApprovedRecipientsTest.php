<?php

namespace Tests\Feature;

use App\Actions\WhatsApp\StoreApprovedRecipients;
use App\Models\User;
use App\Models\WhatsappBroadcastRecipient;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovedRecipientsTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(array $records = [])
    {
        $owner = User::factory()->create();
        $template = $owner->whatsappTemplates()->create([
            'name' => 'promo', 'display_name' => '', 'language' => 'id', 'header_type' => 'NONE', 'body' => 'Halo',
        ]);
        $template->forceFill(['approval_status' => WhatsappTemplate::APPROVED])->save();

        return $owner->whatsappBroadcasts()->create([
            'name' => 'Campaign', 'whatsapp_template_id' => $template->id,
            'recipients' => $records ? array_column($records, 'msisdn') : ['6281234567890'],
            'recipient_records' => $records ?: null, 'recipient_count' => count($records) ?: 1,
        ])->fresh();
    }

    public function test_approve_stores_numbers_and_variables_once_in_batches(): void
    {
        $records = array_map(fn ($i) => [
            'msisdn' => '628'.str_pad((string) $i, 9, '0', STR_PAD_LEFT),
            'nama' => 'Nama '.$i, 'kode' => '001', 'produk' => 'Tas',
        ], range(1, 205));
        $broadcast = $this->campaign($records);
        $this->assertDatabaseCount('whatsapp_broadcast_recipients', 0);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $url = route('admin.whatsapp.broadcasts.review', $broadcast);
        $this->patch($url, ['status' => 'approve', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame(205, $broadcast->approvedRecipients()->count());
        $row = $broadcast->approvedRecipients()->where('msisdn', $records[0]['msisdn'])->sole();
        $this->assertSame(['nama' => 'Nama 1', 'kode' => '001', 'produk' => 'Tas'], $row->variables);
        $this->assertSame($broadcast->id, $row->broadcast->id);
        $this->patch($url, ['status' => 'approve', 'revision' => 1])->assertSessionHasErrors('status');
        $this->patch($url, ['status' => 'finish', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('whatsapp_broadcast_recipients', 205);
    }

    public function test_reject_does_not_store_and_legacy_numbers_get_empty_variables(): void
    {
        $rejected = $this->campaign();
        $legacy = $this->campaign();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->patch(route('admin.whatsapp.broadcasts.review', $rejected), [
            'status' => 'reject', 'revision' => 1, 'rejection_reason' => 'Perbaiki',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('whatsapp_broadcast_recipients', 0);
        $this->patch(route('admin.whatsapp.broadcasts.review', $legacy), [
            'status' => 'approve', 'revision' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame([], WhatsappBroadcastRecipient::sole()->variables);
        $this->assertSame('6281234567890', WhatsappBroadcastRecipient::sole()->msisdn);
    }

    public function test_failed_approval_rolls_back_inserted_recipients_and_status(): void
    {
        $broadcast = $this->campaign();
        $this->mock(StoreApprovedRecipients::class, function ($mock) {
            $mock->shouldReceive('handle')->once()->andReturnUsing(function ($broadcast) {
                (new StoreApprovedRecipients)->handle($broadcast);
                throw new \RuntimeException('Simulated approval failure');
            });
        });
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->patch(route('admin.whatsapp.broadcasts.review', $broadcast), ['status' => 'approve', 'revision' => 1])
            ->assertStatus(500);
        $this->assertDatabaseCount('whatsapp_broadcast_recipients', 0);
        $this->assertSame('pending', $broadcast->fresh()->status);
    }
}
