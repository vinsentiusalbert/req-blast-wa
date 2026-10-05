<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappSender;
use App\Models\WhatsappTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class WhatsAppBroadcastWindowTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-02 02:00:00', 'UTC'));
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $template = $user->whatsappTemplates()->create([
            'name' => 'scheduled', 'language' => 'id', 'display_name' => 'Toko',
            'header_type' => 'NONE', 'body' => 'Halo', 'buttons' => [],
        ]);
        $template->forceFill(['approval_status' => WhatsappTemplate::APPROVED])->save();
        $this->actingAs($user);

        return [
            'name' => 'Scheduled broadcast', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', '6281234567890'),
            'send_date' => '2026-10-02', 'send_time' => '10:30',
            'end_date' => '2026-10-04', 'end_time' => '18:00',
        ];
    }

    public function test_window_is_saved_in_utc_shown_in_local_time_and_cannot_be_changed_after_creation(): void
    {
        $data = $this->payload();
        $user = auth()->user();
        $this->post(route('user.whatsapp.broadcasts.store'), $data)->assertSessionHasNoErrors();
        $broadcast = $user->whatsappBroadcasts()->sole();
        $this->assertSame('2026-10-02 03:30:00', $broadcast->sending_starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-04 11:00:00', $broadcast->sending_ends_at->format('Y-m-d H:i:s'));
        $this->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertOk()->assertSee('02 Oct 2026, 10:30')->assertSee('04 Oct 2026, 18:00');
        $this->get(route('user.whatsapp.broadcasts.edit', $broadcast))->assertForbidden();
        unset($data['recipient_file']);
        $this->travelTo(CarbonImmutable::parse('2026-10-05 00:00:00', 'UTC'));
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), $data)->assertForbidden();
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Rename', 'whatsapp_template_id' => $data['whatsapp_template_id'],
        ])->assertForbidden();
        $this->assertSame('2026-10-02 03:30:00', $broadcast->fresh()->sending_starts_at->format('Y-m-d H:i:s'));
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), array_replace($data, [
            'send_date' => '', 'send_time' => '', 'end_date' => '', 'end_time' => '',
        ]))->assertForbidden();
        $this->assertSame('2026-10-02 03:30:00', $broadcast->fresh()->sending_starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-04 11:00:00', $broadcast->fresh()->sending_ends_at->format('Y-m-d H:i:s'));
        $this->assertSame(1, $broadcast->recipientEntries()->count());
    }

    public function test_incomplete_past_or_reversed_windows_do_not_save_a_broadcast(): void
    {
        $data = $this->payload();
        $url = route('user.whatsapp.broadcasts.store');
        foreach ([
            [['send_time' => ''], 'send_time'],
            [['end_date' => ''], 'end_date'],
            [['send_date' => '2026-02-30'], 'send_date'],
            [['send_time' => '25:00'], 'send_time'],
            [['send_time' => '08:59'], 'send_time'],
            [['end_date' => '2026-10-02', 'end_time' => '10:30'], 'end_time'],
            [['end_date' => '2026-10-01'], 'end_time'],
        ] as [$changes, $error]) {
            $this->post($url, array_replace($data, $changes))->assertSessionHasErrors($error);
        }
        $this->assertDatabaseCount('whatsapp_broadcasts', 0);
        $this->assertDatabaseCount('whatsapp_broadcast_recipients', 0);
    }

    public function test_time_boundaries_and_admin_partitions_respect_the_broadcast_window(): void
    {
        $data = $this->payload();
        $data['end_time'] = '00:00';
        $this->post(route('user.whatsapp.broadcasts.store'), $data)->assertSessionHasNoErrors();
        $broadcast = auth()->user()->whatsappBroadcasts()->sole();
        $this->assertFalse($broadcast->isWithinSendingWindow($broadcast->sending_starts_at->subSecond()));
        $this->assertTrue($broadcast->isWithinSendingWindow($broadcast->sending_starts_at));
        $this->assertTrue($broadcast->isWithinSendingWindow($broadcast->sending_ends_at->subSecond()));
        $this->assertFalse($broadcast->isWithinSendingWindow($broadcast->sending_ends_at));
        $broadcast->forceFill(['status' => 'accepted'])->save();
        WhatsappSender::create(['phone_number' => '628111111111', 'is_active' => true]);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $url = route('admin.whatsapp.campaigns.schedules.update', $broadcast);
        $this->put($url, ['schedules' => [['send_date' => '2026-10-03', 'message_count' => 1]]])->assertSessionHasNoErrors();
        foreach (['2026-10-04', '2026-10-05'] as $date) {
            $this->put($url, ['schedules' => [['send_date' => $date, 'message_count' => 1]]])->assertSessionHasErrors('schedules.0.send_date');
        }
        $this->assertSame('2026-10-03', $broadcast->schedules()->sole()->send_date->format('Y-m-d'));
        $this->get(route('admin.whatsapp.campaigns.show', $broadcast))->assertOk()->assertSee('02 Oct 2026, 10:30')->assertSee('04 Oct 2026, 00:00');
        $this->actingAs($broadcast->user);
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), $data)->assertForbidden();
    }
}
