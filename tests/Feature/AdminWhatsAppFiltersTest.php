<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWhatsAppFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lists_all_owners_and_combines_status_and_user_filters(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $owners = User::factory()->count(2)->create();
        foreach ($owners as $owner) {
            foreach (['pending', 'approved', 'rejected'] as $status) {
                $template = $owner->whatsappTemplates()->create([
                    'name' => 'template_'.$owner->id.'_'.$status, 'display_name' => '',
                    'language' => 'id', 'header_type' => 'NONE', 'body' => 'Hello',
                ]);
                $template->forceFill(['approval_status' => $status])->save();
            }
            foreach (['pending', 'approve', 'reject', 'finish'] as $status) {
                $broadcast = $owner->whatsappBroadcasts()->create([
                    'name' => 'campaign_'.$owner->id.'_'.$status, 'whatsapp_template_id' => $template->id,
                    'recipients' => ['6281234567890'], 'recipient_count' => 1,
                ]);
                $broadcast->forceFill(['status' => $status])->save();
            }
        }
        $this->actingAs($admin);
        foreach ([
            ['templates', 'templates', 'approval_status', 'approved', 6],
            ['broadcasts', 'broadcasts', 'status', 'finish', 8],
        ] as [$route, $variable, $column, $status, $total]) {
            $url = route('admin.whatsapp.'.$route.'.index');
            $this->get($url)->assertOk()->assertViewHas($variable, fn ($rows) => $rows->total() === $total)
                ->assertSee($owners[0]->username)->assertSee($owners[1]->username);
            $this->get($url.'?user_id='.$owners[0]->id)
                ->assertOk()->assertViewHas($variable, fn ($rows) => $rows->total() === $total / 2 && $rows->every(fn ($row) => $row->user_id === $owners[0]->id));
            $this->get($url.'?status='.$status)->assertOk()
                ->assertViewHas($variable, fn ($rows) => $rows->total() === 2 && $rows->every(fn ($row) => $status === $row->$column));
            $this->get($url.'?status='.$status.'&user_id='.$owners[0]->id)->assertOk()
                ->assertViewHas($variable, fn ($rows) => $rows->total() === 1 && $rows->first()->user_id === $owners[0]->id && $rows->first()->$column === $status);
            $this->get($url.'?user_id=invalid')->assertSessionHasErrors('user_id');
            $this->get($url.'?status=invalid')->assertSessionHasErrors('status');
        }
    }
}
