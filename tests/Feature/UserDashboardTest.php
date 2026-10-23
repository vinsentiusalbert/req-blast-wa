<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_zero_counts_for_a_new_user(): void
    {
        $this->actingAs(User::factory()->create())->get(route('user.dashboard'))
            ->assertOk()
            ->assertViewHas('totalTemplates', 0)
            ->assertViewHas('totalCampaigns', 0)
            ->assertViewHas('blockedDrafts', 0)
            ->assertSee('Awaiting approval')
            ->assertSee('Approved')
            ->assertSee('Rejected')
            ->assertSee('No templates yet.')
            ->assertSee('No campaigns yet.');
    }

    public function test_dashboard_counts_statuses_and_excludes_other_users_data(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach ([$user, $other] as $owner) {
            foreach (['pending', 'approved', 'approved', 'rejected'] as $index => $status) {
                $template = $owner->whatsappTemplates()->create([
                    'name' => 'template_'.$index, 'language' => 'id',
                    'display_name' => '', 'header_type' => 'NONE', 'body' => 'Hello',
                ]);
                $template->forceFill(['approval_status' => $status])->save();
                $owner->whatsappBroadcasts()->create([
                    'name' => 'Campaign '.$index, 'whatsapp_template_id' => $template->id,
                    'recipients' => ['628123456789'], 'recipient_count' => 1,
                ]);
            }
        }

        $this->actingAs($user)->get(route('user.dashboard'))
            ->assertOk()
            ->assertViewHas('totalTemplates', 4)
            ->assertViewHas('totalCampaigns', 4)
            ->assertViewHas('blockedDrafts', 2)
            ->assertViewHas('templateCounts', fn ($counts) => $counts->all() === [
                'approved' => 2, 'pending' => 1, 'rejected' => 1,
            ])
            ->assertViewHas('campaignCounts', fn ($counts) => $counts->get('pending') === 4)
            ->assertSee('Overall total')
            ->assertSee('<strong class="stat">8</strong>', false)
            ->assertSee(route('user.whatsapp.templates.index'))
            ->assertSee(route('user.whatsapp.broadcasts.index'));
    }
}
