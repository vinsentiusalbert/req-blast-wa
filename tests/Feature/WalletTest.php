<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WhatsappBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(User $user, int $count = 2): WhatsappBroadcast
    {
        $template = $user->whatsappTemplates()->create(['name' => 'promo', 'display_name' => '', 'language' => 'id', 'header_type' => 'NONE', 'body' => 'Hello']);
        $template->forceFill(['approval_status' => 'approved'])->save();
        $this->actingAs($user)->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Wallet promotion', 'whatsapp_template_id' => $template->id,
            'recipients' => implode("\n", array_map(fn ($i) => '62812345678'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), range(1, $count))),
            'unit_price' => 1, 'charged_amount' => 0,
        ])->assertSessionHasNoErrors();

        return $user->whatsappBroadcasts()->latest('id')->firstOrFail();
    }

    public function test_default_zero_balance_and_default_rate_cannot_be_mass_assigned(): void
    {
        $user = User::create(['name' => 'New', 'username' => 'new.user', 'email' => 'new@example.com', 'password' => 'Secure123!', 'balance' => 99999, 'broadcast_rate' => 1]);
        $this->assertSame(0, $user->fresh()->balance);
        $this->assertSame(500, $user->fresh()->broadcast_rate);
        $this->actingAs($user)->get(route('user.wallet'))->assertOk()->assertSee('IDR 500')->assertSee('No balance transactions yet');
    }

    public function test_only_admin_can_credit_and_update_user_rates_and_credit_is_idempotent(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $credit = route('admin.users.wallet.credit', $user);
        $rate = route('admin.users.wallet.rate', $user);
        $payload = ['amount' => 10000, 'reference' => (string) Str::uuid(), 'note' => 'Deposit'];
        $this->post($credit, $payload)->assertRedirect('/login');
        $this->actingAs($other)->get(route('admin.users.wallet', $user))->assertForbidden();
        $this->post($credit, $payload)->assertForbidden();
        $this->patch($rate, ['broadcast_rate' => 250])->assertForbidden();
        $this->actingAs($admin)->get(route('admin.users.wallet', $user))->assertOk();
        $this->post($credit, $payload)->assertSessionHasNoErrors();
        $this->post($credit, $payload)->assertSessionHasNoErrors();
        $this->assertSame(10000, $user->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->assertSame($admin->id, WalletTransaction::sole()->actor_id);
        $this->post(route('admin.users.wallet.credit', $other), $payload)->assertSessionHasErrors('amount');
        $this->assertSame(0, $other->fresh()->balance);
        $this->patch($rate, ['broadcast_rate' => 250])->assertSessionHasNoErrors();
        $this->assertSame(250, $user->fresh()->broadcast_rate);
        $this->assertSame(500, $other->fresh()->broadcast_rate);
        $this->actingAs($user)->get(route('user.wallet'))->assertOk()->assertSee('Deposit')->assertSee('IDR 10,000');
        $this->actingAs($other)->get(route('user.wallet', ['user_id' => $user->id]))->assertOk()->assertDontSee('Deposit');
    }

    public function test_invalid_credits_and_rates_never_change_balance(): void
    {
        $user = User::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ([0, -1, '1.5', 'abc', User::MAX_BALANCE + 1] as $amount) {
            $this->post(route('admin.users.wallet.credit', $user), ['amount' => $amount, 'reference' => (string) Str::uuid()])->assertSessionHasErrors('amount');
        }
        foreach ([-1, '1.5', User::MAX_BROADCAST_RATE + 1] as $rate) {
            $this->patch(route('admin.users.wallet.rate', $user), ['broadcast_rate' => $rate])->assertSessionHasErrors('broadcast_rate');
        }
        $user->forceFill(['balance' => User::MAX_BALANCE])->save();
        $this->post(route('admin.users.wallet.credit', $user), ['amount' => 1, 'reference' => (string) Str::uuid()])->assertSessionHasErrors('amount');
        $this->assertSame(User::MAX_BALANCE, $user->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_approval_charges_once_at_quoted_rate_and_finish_does_not_charge_again(): void
    {
        $user = User::factory()->funded(2000)->create(['broadcast_rate' => 350]);
        $broadcast = $this->campaign($user);
        $this->assertSame(350, $broadcast->unit_price);
        $this->assertSame(2000, $user->fresh()->balance);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch(route('admin.users.wallet.rate', $user), ['broadcast_rate' => 900])->assertSessionHasNoErrors();
        $url = route('admin.whatsapp.broadcasts.review', $broadcast);
        $this->patch($url, ['status' => 'approve', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1300, $user->fresh()->balance);
        $this->assertSame(700, $broadcast->fresh()->charged_amount);
        $this->assertNotNull($broadcast->fresh()->charged_at);
        $this->assertSame(-700, WalletTransaction::sole()->amount);
        $this->assertSame(2, $broadcast->approvedRecipients()->count());
        WalletTransaction::sole()->update(['note' => 'Legacy generated description']);
        $this->actingAs($user)->get(route('user.wallet'))->assertOk()->assertSee('2 numbers × IDR 350')->assertDontSee('Legacy generated description');
        $this->actingAs($admin);
        $this->patch($url, ['status' => 'approve', 'revision' => 1])->assertSessionHasErrors('status');
        $this->patch($url, ['status' => 'finish', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1300, $user->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_insufficient_balance_blocks_approval_then_topup_allows_exact_balance_charge(): void
    {
        $user = User::factory()->funded(999)->create();
        $broadcast = $this->campaign($user);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $url = route('admin.whatsapp.broadcasts.review', $broadcast);
        $this->patch($url, ['status' => 'approve', 'revision' => 1])->assertSessionHasErrors('balance');
        $this->assertSame('pending', $broadcast->fresh()->status);
        $this->assertSame(999, $user->fresh()->balance);
        $this->assertDatabaseCount('whatsapp_broadcast_recipients', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
        $this->post(route('admin.users.wallet.credit', $user), ['amount' => 1, 'reference' => (string) Str::uuid()])->assertSessionHasNoErrors();
        $this->patch($url, ['status' => 'approve', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame(0, $user->fresh()->balance);
    }

    public function test_rejection_is_free_and_resubmission_quotes_new_rate_and_recipient_count(): void
    {
        $user = User::factory()->funded(2000)->create();
        $broadcast = $this->campaign($user);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch(route('admin.whatsapp.broadcasts.review', $broadcast), ['status' => 'reject', 'revision' => 1, 'rejection_reason' => 'Perbaiki'])->assertSessionHasNoErrors();
        $this->assertSame(2000, $user->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
        $this->patch(route('admin.users.wallet.rate', $user), ['broadcast_rate' => 200])->assertSessionHasNoErrors();
        $this->actingAs($user)->put(route('user.whatsapp.broadcasts.update', $broadcast), ['name' => 'Perbaikan', 'whatsapp_template_id' => $broadcast->whatsapp_template_id, 'recipients' => '6281234567890'])->assertSessionHasNoErrors();
        $this->assertSame(200, $broadcast->fresh()->unit_price);
        $this->assertSame(1, $broadcast->fresh()->recipient_count);
        $this->actingAs($admin)->patch(route('admin.whatsapp.broadcasts.review', $broadcast), ['status' => 'approve', 'revision' => 2])->assertSessionHasNoErrors();
        $this->assertSame(1800, $user->fresh()->balance);
    }

    public function test_two_broadcasts_cannot_spend_same_balance_and_zero_rate_is_allowed(): void
    {
        $user = User::factory()->funded(1000)->create();
        $first = $this->campaign($user, 1);
        // Reuse the approved template to avoid the unique template-name constraint.
        $this->post(route('user.whatsapp.broadcasts.store'), ['name' => 'Second', 'whatsapp_template_id' => $first->whatsapp_template_id, 'recipients' => "6281234567891\n6281234567892"])->assertSessionHasNoErrors();
        $second = $user->whatsappBroadcasts()->latest('id')->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch(route('admin.whatsapp.broadcasts.review', $first), ['status' => 'approve', 'revision' => 1])->assertSessionHasNoErrors();
        $this->patch(route('admin.whatsapp.broadcasts.review', $second), ['status' => 'approve', 'revision' => 1])->assertSessionHasErrors('balance');
        $this->assertSame(500, $user->fresh()->balance);
        $this->patch(route('admin.users.wallet.rate', $user), ['broadcast_rate' => 0])->assertSessionHasNoErrors();
        $this->actingAs($user)->put(route('user.whatsapp.broadcasts.update', $second), ['name' => 'Free', 'whatsapp_template_id' => $first->whatsapp_template_id])->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('admin.whatsapp.broadcasts.review', $second), ['status' => 'approve', 'revision' => 2])->assertSessionHasNoErrors();
        $this->assertSame(500, $user->fresh()->balance);
        $this->assertSame(0, $second->fresh()->charged_amount);
    }

    public function test_legacy_approved_broadcast_is_not_billed_retroactively(): void
    {
        $user = User::factory()->create();
        $broadcast = $this->campaign($user);
        $broadcast->forceFill(['status' => 'approve'])->save();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->patch(route('admin.whatsapp.broadcasts.review', $broadcast), ['status' => 'finish', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame('finish', $broadcast->fresh()->status);
        $this->assertSame(0, $user->fresh()->balance);
        $this->assertNull($broadcast->fresh()->charged_at);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }
}
