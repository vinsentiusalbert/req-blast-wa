<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_reject_requires_reason_owner_can_read_and_resubmit_and_stale_review_fails(): void
    {
        $user = User::factory()->funded()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $other = User::factory()->create();
        $template = $user->whatsappTemplates()->create([
            'name' => 'promo', 'display_name' => '', 'language' => 'id', 'header_type' => 'NONE', 'body' => 'Hello',
        ]);
        $template->forceFill(['approval_status' => WhatsappTemplate::APPROVED])->save();
        $this->actingAs($user)->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Iklan', 'whatsapp_template_id' => $template->id, 'recipients' => '6281234567890',
        ])->assertSessionHasNoErrors();
        $broadcast = $user->whatsappBroadcasts()->sole();
        $review = route('admin.whatsapp.broadcasts.review', $broadcast);
        $this->patch($review, ['status' => 'reject', 'revision' => 1, 'rejection_reason' => 'Unauthorized'])->assertForbidden();
        $this->actingAs($admin);
        foreach (['', '   ', str_repeat('a', 2001)] as $reason) {
            $this->patch($review, ['status' => 'reject', 'revision' => 1, 'rejection_reason' => $reason])
                ->assertSessionHasErrors('rejection_reason');
            $this->assertSame('pending', $broadcast->fresh()->status);
        }
        $reason = 'Revise the recipient list <script>alert(1)</script>';
        $this->patch($review, ['status' => 'reject', 'revision' => 1, 'rejection_reason' => $reason])->assertSessionHasNoErrors();
        $this->assertSame('reject', $broadcast->fresh()->status);
        $this->assertSame($admin->id, $broadcast->fresh()->rejected_by);
        $this->assertNotNull($broadcast->fresh()->rejected_at);
        $this->get(route('admin.whatsapp.broadcasts.index', ['status' => 'reject']))->assertOk()->assertSee('Iklan');
        $this->actingAs($other)->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertForbidden();
        $this->actingAs($user)->get(route('user.whatsapp.broadcasts.show', $broadcast))
            ->assertOk()->assertSee($reason)->assertDontSee('<script>alert(1)</script>', false)->assertSee('Edit Broadcast');
        $this->get(route('user.whatsapp.broadcasts.edit', $broadcast))->assertOk()->assertSee($reason);
        $this->get(route('user.whatsapp.broadcasts.index'))->assertOk()->assertSee('Reject')->assertSee('Revise the recipient list');
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Iklan diperbaiki', 'whatsapp_template_id' => $template->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame('pending', $broadcast->fresh()->status);
        $this->assertNull($broadcast->fresh()->rejection_reason);
        $this->assertSame(2, $broadcast->fresh()->revision);
        $this->actingAs($admin)->patch($review, ['status' => 'reject', 'revision' => 1, 'rejection_reason' => 'Old'])
            ->assertSessionHasErrors('status');
        $this->patch($review, ['status' => 'approve', 'revision' => 2])->assertSessionHasNoErrors();
        $this->patch($review, ['status' => 'reject', 'revision' => 2, 'rejection_reason' => 'Terlambat'])
            ->assertSessionHasErrors('status');
        $this->assertSame('approve', $broadcast->fresh()->status);
    }

    public function test_admin_sees_message_details_csv_variables_and_personalized_preview(): void
    {
        $user = User::factory()->funded()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $template = $user->whatsappTemplates()->create([
            'name' => 'promo', 'display_name' => '', 'language' => 'id',
            'header_type' => 'TEXT', 'header_text' => 'Hello {{name}}', 'body' => 'Product {{product}}',
        ]);
        $broadcast = $user->whatsappBroadcasts()->create([
            'name' => 'Iklan', 'whatsapp_template_id' => $template->id,
            'recipients' => ['6281234567890', '6281234567891'], 'recipient_count' => 2,
            'recipient_records' => [
                ['msisdn' => '6281234567890', 'name' => 'Alex', 'product' => 'Shoes'],
                ['msisdn' => '6281234567891', 'name' => 'Sam', 'product' => '<script>alert(1)</script>'],
            ],
        ]);
        $this->actingAs($admin)->get(route('admin.whatsapp.broadcasts.show', $broadcast))
            ->assertOk()->assertSeeInOrder(['Message details', 'Hello {{name}}', 'Product {{product}}', 'Recipients and variables'])
            ->assertSee('Sam')->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Hello Alex')->assertSee('Product Shoes');
        $template->update(['header_type' => 'IMAGE', 'asset_path' => 'headers/example.png']);
        $this->get(route('admin.whatsapp.broadcasts.show', $broadcast))->assertOk()
            ->assertSee('Message header image')->assertSee(route('admin.whatsapp.templates.asset', $template));
    }

    public function test_admin_workflow_and_user_edit_lock(): void
    {
        $user = User::factory()->funded()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $template = $user->whatsappTemplates()->create(['name' => 'promo', 'display_name' => '', 'language' => 'id', 'header_type' => 'NONE', 'body' => 'Hello']);
        $template->forceFill(['approval_status' => WhatsappTemplate::APPROVED])->save();
        $this->actingAs($user)->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Iklan', 'whatsapp_template_id' => $template->id, 'recipients' => '6281234567890', 'status' => 'finish',
        ])->assertSessionHasNoErrors();
        $broadcast = $user->whatsappBroadcasts()->sole();
        $this->assertSame('pending', $broadcast->status);
        $review = route('admin.whatsapp.broadcasts.review', $broadcast);
        $this->get(route('admin.whatsapp.broadcasts.index'))->assertForbidden();
        $this->patch($review, ['status' => 'approve', 'revision' => 1])->assertForbidden();
        $this->actingAs($admin)->get(route('admin.whatsapp.broadcasts.index'))->assertOk()->assertSee('Iklan');
        $this->get(route('admin.whatsapp.broadcasts.show', $broadcast))->assertOk()->assertSee('Approve');
        $this->patch($review, ['status' => 'finish', 'revision' => 1])->assertSessionHasErrors('status');
        $this->patch($review, ['status' => 'approve', 'revision' => 99])->assertSessionHasErrors('status');
        $this->patch($review, ['status' => 'approve', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame('approve', $broadcast->fresh()->status);
        $this->assertSame($admin->id, $broadcast->fresh()->approved_by);
        $this->actingAs($user)->put(route('user.whatsapp.broadcasts.update', $broadcast), [])->assertForbidden();
        $this->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertOk()->assertSee('Approve')->assertDontSee('Edit Broadcast');
        $this->actingAs($admin)->patch($review, ['status' => 'finish', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame('finish', $broadcast->fresh()->status);
        $this->assertNotNull($broadcast->fresh()->finished_at);
        $this->patch($review, ['status' => 'approve', 'revision' => 1])->assertSessionHasErrors('status');
        $this->actingAs($user)->get(route('user.whatsapp.broadcasts.index'))->assertOk()->assertSee('Finish');
    }
}
