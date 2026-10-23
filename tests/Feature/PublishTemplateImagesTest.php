<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublishTemplateImagesTest extends TestCase
{
    use RefreshDatabase;

    private function template(string $path): WhatsappTemplate
    {
        return User::factory()->create()->whatsappTemplates()->create([
            'name' => 'public_image', 'display_name' => '', 'language' => 'en',
            'header_type' => 'IMAGE', 'asset_path' => $path, 'body' => 'Hello',
        ]);
    }

    public function test_command_copies_existing_private_images_preserves_originals_and_can_run_again(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $path = 'whatsapp-templates/1/existing.png';
        $template = $this->template($path);
        Storage::disk('local')->put($path, 'image fixture');
        $this->artisan('whatsapp:publish-template-images')->expectsOutputToContain('Copied: 1')->assertSuccessful();
        Storage::disk('local')->assertExists($path);
        $this->assertSame('image fixture', Storage::disk('public')->get($path));
        $this->assertSame($path, $template->fresh()->asset_path);
        $this->artisan('whatsapp:publish-template-images')->expectsOutputToContain('already public: 1')->assertSuccessful();
    }

    public function test_missing_images_are_reported_and_arbitrary_private_files_are_never_published(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->template('whatsapp-templates/1/missing.png');
        $this->template('../secret.png');
        Storage::disk('local')->put('secret.png', 'private fixture');
        $this->artisan('whatsapp:publish-template-images')->expectsOutputToContain('missing/failed: 1')->assertFailed();
        Storage::disk('public')->assertMissing('secret.png');
    }

    public function test_legacy_private_images_still_work_through_authorized_preview(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $path = 'whatsapp-templates/1/legacy.png';
        $template = $this->template($path);
        Storage::disk('local')->put($path, 'image fixture');
        $this->actingAs($template->user)->get(route('user.whatsapp.templates.asset', $template))
            ->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
