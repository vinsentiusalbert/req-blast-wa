<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_are_scoped_to_user_and_global_for_admin(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        foreach ([$owner, $other] as $user) {
            foreach (array_keys(WhatsappTemplate::APPROVAL_LABELS) as $status) {
                $template = $user->whatsappTemplates()->create([
                    'name' => 'template_'.$status, 'language' => 'id', 'display_name' => '',
                    'header_type' => 'NONE', 'body' => 'Pesan', 'buttons' => [],
                ]);
                $template->forceFill(['approval_status' => $status])->save();
                if ($status === WhatsappTemplate::APPROVED) {
                    $user->whatsappBroadcasts()->create([
                        'name' => 'Promo', 'whatsapp_template_id' => $template->id,
                        'recipients' => ['6281234567890'], 'recipient_count' => 1,
                    ]);
                }
            }
        }

        $this->actingAs($owner)->get(route('user.dashboard'))->assertOk()
            ->assertViewHas('totalTemplates', 3)
            ->assertViewHas('templateCounts', fn ($counts) => $counts->all() === ['pending' => 1, 'approved' => 1, 'rejected' => 1])
            ->assertViewHas('totalBroadcasts', 1)
            ->assertViewHas('broadcastCounts', fn ($counts) => (int) $counts['draft'] === 1)
            ->assertSee('Template disetujui')->assertSee('Broadcast draft');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('totalTemplates', 6)
            ->assertViewHas('templateCounts', fn ($counts) => $counts->all() === ['pending' => 2, 'approved' => 2, 'rejected' => 2])
            ->assertViewHas('totalBroadcasts', 2)
            ->assertViewHas('broadcastCounts', fn ($counts) => (int) $counts['draft'] === 2);
    }

    public function test_empty_dashboard_shows_zero_counts(): void
    {
        $this->actingAs(User::factory()->create())->get(route('user.dashboard'))->assertOk()
            ->assertViewHas('totalTemplates', 0)
            ->assertViewHas('totalBroadcasts', 0)
            ->assertViewHas('templateCounts', fn ($counts) => $counts->every(fn ($count) => $count === 0))
            ->assertViewHas('broadcastCounts', fn ($counts) => $counts->all() === ['draft' => 0])
            ->assertSee('Broadcast draft');
    }
}
