<?php

namespace Tests\Feature;

use App\Actions\WhatsApp\SaveBroadcastDraft;
use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WhatsAppApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'promo_review', 'language' => 'id', 'display_name' => '',
            'header_type' => 'NONE', 'body' => 'Halo pelanggan!', 'buttons' => [],
        ], $overrides);
    }

    private function submit(User $user): WhatsappTemplate
    {
        $this->actingAs($user)->post(route('user.whatsapp.templates.store'), $this->payload([
            'approval_status' => 'approved', 'revision' => 999, 'reviewed_by' => $user->id,
        ]))->assertSessionHasNoErrors();

        return $user->whatsappTemplates()->sole();
    }

    public function test_blank_display_name_and_pending_submission_cannot_be_bypassed(): void
    {
        $user = User::factory()->create();
        $template = $this->submit($user);
        $this->assertSame('', $template->display_name);
        $this->assertSame(WhatsappTemplate::PENDING, $template->approval_status);
        $this->assertSame(1, $template->revision);
        $this->assertNull($template->reviewed_by);
        $this->get(route('user.whatsapp.broadcasts.create'))->assertRedirect(route('user.whatsapp.broadcasts.index'));
        $this->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Bypass', 'whatsapp_template_id' => $template->id, 'recipients' => '081234567890',
        ])->assertSessionHasErrors('whatsapp_template_id');
        $this->assertDatabaseCount('whatsapp_broadcasts', 0);
    }

    public function test_admin_can_review_and_approve_before_user_creates_broadcast(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $template = $this->submit($user);
        $this->actingAs($admin)->get(route('admin.whatsapp.templates.index'))->assertOk()->assertSee($template->name);
        $this->get(route('admin.whatsapp.templates.show', $template))->assertOk()->assertSee('Setujui Template');
        $this->patch(route('admin.whatsapp.templates.review', $template), [
            'decision' => 'approved', 'revision' => 1, 'review_note' => 'Pesan sesuai.',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($template->fresh()->isApproved());
        $this->assertSame($admin->id, $template->fresh()->reviewed_by);
        $this->assertNotNull($template->fresh()->reviewed_at);
        $this->get(route('admin.whatsapp.templates.index', ['status' => 'approved']))->assertOk()->assertSee($template->name);
        $this->actingAs($user)->get(route('user.whatsapp.broadcasts.create'))->assertOk()->assertSee($template->name);
        $this->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Approved broadcast', 'whatsapp_template_id' => $template->id, 'recipients' => '081234567890',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('whatsapp_broadcasts', 1);
    }

    public function test_rejection_requires_reason_and_edit_resubmits_for_review(): void
    {
        $user = User::factory()->create();
        $template = $this->submit($user);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->patch(route('admin.whatsapp.templates.review', $template), [
            'decision' => 'rejected', 'revision' => 1,
        ])->assertSessionHasErrors('review_note');
        $this->patch(route('admin.whatsapp.templates.review', $template), [
            'decision' => 'rejected', 'revision' => 1, 'review_note' => 'Perjelas periode promo.',
        ])->assertSessionHasNoErrors();
        $this->assertSame(WhatsappTemplate::REJECTED, $template->fresh()->approval_status);
        $this->actingAs($user)->get(route('user.whatsapp.templates.show', $template))->assertOk()->assertSee('Perjelas periode promo.');
        $this->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Blocked', 'whatsapp_template_id' => $template->id, 'recipients' => '081234567890',
        ])->assertSessionHasErrors('whatsapp_template_id');
        $this->put(route('user.whatsapp.templates.update', $template), $this->payload(['body' => 'Promo berlaku 1–7 Oktober.']))
            ->assertSessionHasNoErrors();
        $this->assertSame(WhatsappTemplate::PENDING, $template->fresh()->approval_status);
        $this->assertSame(2, $template->fresh()->revision);
        $this->assertNull($template->fresh()->review_note);
    }

    public function test_editing_approved_template_blocks_existing_drafts_until_reapproval(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $template = $this->submit($user);
        $this->actingAs($admin)->patch(route('admin.whatsapp.templates.review', $template), [
            'decision' => 'approved', 'revision' => 1,
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Draft', 'whatsapp_template_id' => $template->id, 'recipients' => '081234567890',
        ])->assertSessionHasNoErrors();
        $broadcast = $user->whatsappBroadcasts()->sole();
        $this->put(route('user.whatsapp.templates.update', $template), $this->payload(['body' => 'Isi baru']))
            ->assertSessionHasNoErrors();
        $this->assertSame(WhatsappTemplate::PENDING, $template->fresh()->approval_status);
        $this->assertNull($template->fresh()->reviewed_at);
        $this->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertOk()->assertSee('Draft terblokir');
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Attempt', 'whatsapp_template_id' => $template->id, 'recipients' => '081234567890',
        ])->assertSessionHasErrors('whatsapp_template_id');
        $this->actingAs($admin)->patch(route('admin.whatsapp.templates.review', $template), [
            'decision' => 'approved', 'revision' => 2,
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Resumed', 'whatsapp_template_id' => $template->id, 'recipients' => '081234567890',
        ])->assertSessionHasNoErrors();
    }

    public function test_user_cannot_review_and_admin_can_access_private_review_image(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('user.whatsapp.templates.store'), $this->payload([
            'header_type' => 'IMAGE', 'asset' => UploadedFile::fake()->image('review.png'),
        ]))->assertSessionHasNoErrors();
        $template = $user->whatsappTemplates()->sole();
        $this->get(route('admin.whatsapp.templates.index'))->assertForbidden();
        $this->get(route('admin.whatsapp.templates.show', $template))->assertForbidden();
        $this->patch(route('admin.whatsapp.templates.review', $template), ['decision' => 'approved', 'revision' => 1])->assertForbidden();
        $this->get(route('admin.whatsapp.templates.asset', $template))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.whatsapp.templates.asset', $template))->assertOk();
    }

    public function test_stale_or_duplicate_review_cannot_approve_changed_content(): void
    {
        $user = User::factory()->create();
        $template = $this->submit($user);
        $this->put(route('user.whatsapp.templates.update', $template), $this->payload(['body' => 'Edited during review']))
            ->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->patch(route('admin.whatsapp.templates.review', $template), ['decision' => 'approved', 'revision' => 1])
            ->assertSessionHasErrors('decision');
        $this->assertSame(WhatsappTemplate::PENDING, $template->fresh()->approval_status);
        $this->patch(route('admin.whatsapp.templates.review', $template), ['decision' => 'approved', 'revision' => 2])
            ->assertSessionHasNoErrors();
        $this->patch(route('admin.whatsapp.templates.review', $template), ['decision' => 'rejected', 'revision' => 2, 'review_note' => 'Duplicate'])
            ->assertSessionHasErrors('decision');
        $this->assertTrue($template->fresh()->isApproved());
    }

    public function test_action_checks_approval_again_after_request_validation(): void
    {
        $user = User::factory()->create();
        $template = $this->submit($user);
        $this->expectException(ValidationException::class);
        app(SaveBroadcastDraft::class)->handle($user, [
            'name' => 'Bypass request', 'whatsapp_template_id' => $template->id, 'recipients' => '081234567890',
        ]);
    }
}
