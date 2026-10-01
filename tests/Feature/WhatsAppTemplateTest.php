<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'promo_akhir_pekan', 'language' => 'id',
            'header_type' => 'TEXT', 'header_text' => 'Promo spesial',
            'body' => 'Halo! Nikmati promo akhir pekan kami.',
        ], $overrides);
    }

    public function test_user_can_create_view_edit_and_search_a_template(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get(route('user.whatsapp.templates.create'))->assertOk()->assertSee('Template Preview')->assertDontSee('@hidden', false)->assertDontSee('template-display-name', false)->assertDontSee('data-add-button', false);
        $this->post(route('user.whatsapp.templates.store'), $this->payload(['user_id' => 999, 'status' => 'approved']))
            ->assertSessionHasNoErrors();
        $template = $user->whatsappTemplates()->sole();
        $this->assertNull($template->footer);
        $this->assertSame('', $template->display_name);
        $this->assertSame('promo_akhir_pekan', $template->name);
        $this->assertSame([], $template->buttons);
        $this->assertSame($user->id, $template->user_id);
        $this->get(route('user.whatsapp.templates.show', $template))->assertOk()->assertSee('Menunggu persetujuan');
        $this->get(route('user.whatsapp.templates.edit', $template))->assertOk()->assertSee('promo_akhir_pekan');
        $this->put(route('user.whatsapp.templates.update', $template), $this->payload(['body' => 'Pesan baru', 'display_name' => 'Sender lama', 'footer' => 'Footer lama']))
            ->assertRedirect(route('user.whatsapp.templates.show', $template))->assertSessionHasNoErrors();
        $this->assertSame('Pesan baru', $template->fresh()->body);
        $this->assertNull($template->fresh()->footer);
        $this->assertSame('', $template->fresh()->display_name);
        $this->get(route('user.whatsapp.templates.index', ['search' => 'akhir', 'language' => 'id']))->assertOk()->assertSee($template->name);
        $this->get(route('user.whatsapp.templates.index', ['search' => 'missing']))->assertOk()->assertDontSee($template->name);
    }

    public function test_template_names_are_unique_per_user(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($first)->post(route('user.whatsapp.templates.store'), $this->payload())->assertSessionHasNoErrors();
        $this->post(route('user.whatsapp.templates.store'), $this->payload())->assertSessionHasErrors('name');
        $this->actingAs($second)->post(route('user.whatsapp.templates.store'), $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('whatsapp_templates', 2);
    }

    public function test_users_cannot_read_update_or_fetch_another_users_template_asset(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('user.whatsapp.templates.store'), $this->payload([
            'header_type' => 'IMAGE', 'asset' => UploadedFile::fake()->image('header.png'),
        ]))->assertSessionHasNoErrors();
        $template = $owner->whatsappTemplates()->sole();
        $this->actingAs(User::factory()->create());
        foreach (['show', 'edit', 'asset'] as $action) {
            $this->get(route('user.whatsapp.templates.'.$action, $template))->assertForbidden();
        }
        $this->put(route('user.whatsapp.templates.update', $template), $this->payload())->assertForbidden();
        $this->get(route('user.whatsapp.templates.index'))->assertOk()->assertDontSee($template->name);
    }

    public function test_image_upload_replacement_and_header_switch_clean_up_private_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('user.whatsapp.templates.store'), $this->payload([
            'header_type' => 'IMAGE', 'asset' => UploadedFile::fake()->image('header.png'),
        ]))->assertSessionHasNoErrors();
        $template = $user->whatsappTemplates()->sole();
        $oldPath = $template->asset_path;
        Storage::disk('local')->assertExists($oldPath);
        $this->get(route('user.whatsapp.templates.asset', $template))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->put(route('user.whatsapp.templates.update', $template), $this->payload(['header_type' => 'IMAGE']))
            ->assertSessionHasNoErrors();
        $this->assertSame($oldPath, $template->fresh()->asset_path);
        $this->put(route('user.whatsapp.templates.update', $template), $this->payload([
            'header_type' => 'IMAGE', 'asset' => UploadedFile::fake()->image('replacement.jpg')->size(1023),
        ]))->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($oldPath);
        $newPath = $template->fresh()->asset_path;
        Storage::disk('local')->assertExists($newPath);
        $this->put(route('user.whatsapp.templates.update', $template), $this->payload(['header_type' => 'NONE']))
            ->assertSessionHasNoErrors();
        $this->assertNull($template->fresh()->header_text);
        $this->assertNull($template->fresh()->asset_path);
        Storage::disk('local')->assertMissing($newPath);
    }

    public function test_invalid_template_content_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $cases = [
            [['name' => 'Invalid Name'], 'name'],
            [['language' => 'invalid'], 'language'],
            [['body' => str_repeat('a', 551)], 'body'],
            [['header_text' => ''], 'header_text'],
            [['header_type' => 'IMAGE'], 'asset'],
            [['header_type' => 'IMAGE', 'asset' => UploadedFile::fake()->create('payload.svg', 1, 'image/svg+xml')], 'asset'],
            [['header_type' => 'IMAGE', 'asset' => UploadedFile::fake()->image('exact-limit.png')->size(1024)], 'asset'],
            [['header_type' => 'IMAGE', 'asset' => UploadedFile::fake()->image('large.png')->size(1025)], 'asset'],
        ];
        foreach ($cases as [$overrides, $error]) {
            $this->post(route('user.whatsapp.templates.store'), $this->payload($overrides))->assertSessionHasErrors($error);
        }
        $this->assertDatabaseCount('whatsapp_templates', 0);
    }

    public function test_buttons_are_ignored_and_user_content_is_escaped(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('user.whatsapp.templates.store'), $this->payload([
            'body' => '<script>alert("xss")</script>',
            'buttons' => [
                ['type' => 'QUICK_REPLY', 'label' => '<b>Hello</b>', 'url' => 'javascript:alert(1)'],
                ['type' => 'PHONE_NUMBER', 'label' => 'Telepon', 'phone' => '+628123456789'],
            ],
        ]))->assertSessionHasNoErrors();
        $template = $user->whatsappTemplates()->sole();
        $this->assertSame([], $template->buttons);
        $this->get(route('user.whatsapp.templates.show', $template))
            ->assertOk()->assertSee('<script>alert("xss")</script>')->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_guest_and_admin_cannot_access_user_whatsapp_routes(): void
    {
        $this->get(route('user.whatsapp.templates.index'))->assertRedirect('/login');
        $this->post(route('user.whatsapp.templates.store'), $this->payload())->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('user.whatsapp.templates.index'))->assertForbidden();
        $this->post(route('user.whatsapp.templates.store'), $this->payload())->assertForbidden();
        $this->get(route('user.whatsapp.broadcasts.index'))->assertForbidden();
        $this->post(route('user.whatsapp.broadcasts.store'), [])->assertForbidden();
    }
}
