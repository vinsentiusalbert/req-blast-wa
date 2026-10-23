<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappSenderAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WhatsappSenderAccountTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Sender A', 'sender' => '6281234567890',
            'api_base_url' => 'https://gateway.example', 'api_key' => 'fake-secret-never-render',
            'status' => 'active', 'min_delay_seconds' => 30, 'max_delay_seconds' => 60,
            'daily_limit' => 100, 'failure_cooldown_seconds' => 900, 'failure_threshold' => 3,
        ], $overrides);
    }

    public function test_only_admins_can_view_create_and_update_sender_settings(): void
    {
        $sender = WhatsappSenderAccount::create($this->payload());
        $this->get(route('admin.whatsapp.senders.index'))->assertRedirect('/login');
        $this->post(route('admin.whatsapp.senders.store'), $this->payload())->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        foreach (['index' => [], 'create' => [], 'edit' => [$sender]] as $action => $params) {
            $this->get(route('admin.whatsapp.senders.'.$action, $params))->assertForbidden();
        }
        $this->post(route('admin.whatsapp.senders.store'), $this->payload())->assertForbidden();
        $this->put(route('admin.whatsapp.senders.update', $sender), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('whatsapp_sender_accounts', 1);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('admin.whatsapp.senders.index'))->assertOk()->assertSee('30')->assertSee('Active')->assertDontSee('fake-secret-never-render');
        $this->get(route('admin.whatsapp.senders.create'))->assertOk()->assertSee('Minimum delay (seconds)')->assertSee('Maximum delay (seconds)');
        $this->get(route('admin.whatsapp.senders.edit', $sender))->assertOk()->assertDontSee('fake-secret-never-render');
    }

    public function test_accounts_store_encrypted_keys_and_are_hidden_from_serialization(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.whatsapp.senders.store'), $this->payload())->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.whatsapp.senders.index'));
        $sender = WhatsappSenderAccount::sole();
        $raw = DB::table('whatsapp_sender_accounts')->value('api_key');
        $this->assertNotSame('fake-secret-never-render', $raw);
        $this->assertSame('fake-secret-never-render', $sender->api_key);
        $this->assertArrayNotHasKey('api_key', $sender->toArray());
        $this->assertArrayNotHasKey('lease_token', $sender->toArray());
    }

    public function test_update_changes_ranges_and_status_preserving_key_and_worker_counters(): void
    {
        $sender = WhatsappSenderAccount::create($this->payload());
        $sender->forceFill(['daily_attempts' => 99, 'consecutive_failures' => 2,
            'next_available_at' => now()->addMinutes(5), 'lease_token' => 'worker-token'])->save();
        $cooldown = $sender->fresh()->next_available_at;
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put(route('admin.whatsapp.senders.update', $sender), $this->payload([
                'api_key' => '', 'status' => 'inactive', 'min_delay_seconds' => 120, 'max_delay_seconds' => 180,
                'daily_attempts' => 0, 'consecutive_failures' => 0, 'next_available_at' => null,
            ]))->assertSessionHasNoErrors();
        $sender->refresh();
        $this->assertSame('inactive', $sender->status);
        $this->assertSame(120, $sender->min_delay_seconds);
        $this->assertSame(180, $sender->max_delay_seconds);
        $this->assertSame('fake-secret-never-render', $sender->api_key);
        $this->assertSame(99, $sender->daily_attempts);
        $this->assertSame(2, $sender->consecutive_failures);
        $this->assertTrue($cooldown->equalTo($sender->next_available_at));
        $this->assertSame('worker-token', $sender->lease_token);
        $this->put(route('admin.whatsapp.senders.update', $sender), $this->payload(['api_key' => 'replacement-secret']))->assertSessionHasNoErrors();
        $this->assertSame('replacement-secret', $sender->fresh()->api_key);
    }

    public function test_invalid_ranges_duplicates_and_statuses_are_rejected_without_flashing_key(): void
    {
        $sender = WhatsappSenderAccount::create($this->payload());
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ([
            ['min_delay_seconds' => 0], ['min_delay_seconds' => '1.5'],
            ['min_delay_seconds' => 60, 'max_delay_seconds' => 30],
            ['max_delay_seconds' => 86401], ['status' => 'bogus'], ['daily_limit' => 0],
            ['failure_threshold' => 0], ['failure_cooldown_seconds' => 10],
            ['api_base_url' => 'https://username:secret@gateway.example'],
            ['api_base_url' => 'https://gateway.example?token=secret'], ['api_base_url' => ['bad']],
        ] as $invalid) {
            $this->put(route('admin.whatsapp.senders.update', $sender), $this->payload($invalid))
                ->assertSessionHasErrors()->assertSessionMissing('_old_input.api_key');
        }
        $this->post(route('admin.whatsapp.senders.store'), $this->payload())->assertSessionHasErrors('sender');
        $this->put(route('admin.whatsapp.senders.update', $sender), $this->payload([
            'min_delay_seconds' => 45, 'max_delay_seconds' => 45,
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('whatsapp_sender_accounts', 1);
    }
}
