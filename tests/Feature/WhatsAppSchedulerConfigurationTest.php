<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappSender;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppSchedulerConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(): WhatsappBroadcast
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $template = $user->whatsappTemplates()->create([
            'name' => 'promo', 'language' => 'id', 'display_name' => 'Toko',
            'header_type' => 'NONE', 'body' => 'Halo', 'buttons' => [],
        ]);
        $template->forceFill(['approval_status' => WhatsappTemplate::APPROVED])->save();
        $campaign = $user->whatsappBroadcasts()->create([
            'name' => 'Scheduled campaign', 'whatsapp_template_id' => $template->id,
            'recipients' => ['6281234567890', '6281234567891', '6281234567892'], 'recipient_count' => 3,
        ]);
        $campaign->forceFill(['status' => 'accepted'])->save();
        foreach ($campaign->recipients as $number) {
            $campaign->recipientEntries()->create(['user_id' => $user->id, 'phone_number' => $number]);
        }

        return $campaign;
    }

    public function test_admin_partitions_all_recipients_and_cannot_change_started_queue(): void
    {
        $campaign = $this->campaign();
        $sender = WhatsappSender::create(['phone_number' => '628111111111', 'is_active' => true]);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $rows = [
            ['send_date' => now('Asia/Bangkok')->addDays(2)->toDateString(), 'message_count' => 1],
            ['send_date' => now('Asia/Bangkok')->addDay()->toDateString(), 'message_count' => 2],
        ];
        $url = route('admin.whatsapp.campaigns.schedules.update', $campaign);
        $this->put($url, ['schedules' => $rows])->assertSessionHasNoErrors();
        $this->assertSame(2, $campaign->schedules()->count());
        $this->assertSame(3, $campaign->recipientEntries()->whereNotNull('whatsapp_campaign_schedule_id')->count());
        $first = $campaign->schedules()->orderBy('send_date')->first();
        $this->assertSame(2, $campaign->recipientEntries()->where('whatsapp_campaign_schedule_id', $first->id)->count());
        $this->get(route('admin.whatsapp.campaigns.show', $campaign))->assertOk()->assertSee('Partisi jadwal pengiriman');
        $invalid = $rows;
        $invalid[0]['message_count'] = 2;
        $this->put($url, ['schedules' => $invalid])->assertSessionHasErrors('schedules');
        $this->assertSame(2, $campaign->schedules()->count());
        $sender->update(['is_active' => false]);
        $this->put($url, ['schedules' => $rows])->assertSessionHasErrors('schedules');
        $sender->update(['is_active' => true]);
        $this->put($url, ['schedules' => $rows])->assertSessionHasNoErrors();
        $this->patch(route('admin.whatsapp.campaigns.update', $campaign), ['status' => 'draft'])->assertSessionHasErrors('status');
        $entry = $campaign->recipientEntries()->first();
        $entry->forceFill(['delivery_status' => 'sent', 'sent_at' => now()])->save();
        $this->put($url, ['schedules' => $rows])->assertSessionHasErrors('schedules');
        $this->assertSame('sent', $entry->fresh()->delivery_status);
    }

    public function test_sender_management_is_admin_only_and_numbers_are_normalized(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_USER]);
        $campaign = $this->campaign();
        $this->actingAs($client)->get(route('admin.whatsapp.senders.index'))->assertForbidden();
        $this->post(route('admin.whatsapp.senders.store'), [])->assertForbidden();
        $this->put(route('admin.whatsapp.campaigns.schedules.update', $campaign), [])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->post(route('admin.whatsapp.senders.store'), ['phone_number' => '081234567890', 'is_active' => 1])->assertSessionHasNoErrors();
        $sender = WhatsappSender::sole();
        $this->assertSame('6281234567890', $sender->phone_number);
        $this->post(route('admin.whatsapp.senders.store'), ['phone_number' => '+6281234567890', 'is_active' => 1])->assertSessionHasErrors('phone_number');
        $this->patch(route('admin.whatsapp.senders.update', $sender), ['is_active' => 0])->assertSessionHasNoErrors();
        $this->assertFalse($sender->fresh()->is_active);
        $this->get(route('admin.whatsapp.senders.index'))->assertOk()->assertSee('Nonaktif');
    }

    public function test_schedule_requires_approval_and_valid_date(): void
    {
        $campaign = $this->campaign();
        $sender = WhatsappSender::create(['phone_number' => '628111111111', 'is_active' => true]);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $rows = [['send_date' => now('Asia/Bangkok')->addDay()->toDateString(), 'message_count' => 3]];
        $url = route('admin.whatsapp.campaigns.schedules.update', $campaign);
        $campaign->forceFill(['status' => 'draft'])->save();
        $this->put($url, ['schedules' => $rows])->assertSessionHasErrors('schedules');
        $campaign->forceFill(['status' => 'accepted'])->save();
        $rows[0]['send_date'] = now('Asia/Bangkok')->subDay()->toDateString();
        $this->put($url, ['schedules' => $rows])->assertSessionHasErrors('schedules.0.send_date');
        $this->assertSame(0, $campaign->schedules()->count());
    }
}
