<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnglishInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_defaults_assets_and_validation(): void
    {
        $this->assertSame('en', config('app.locale'));
        $this->assertSame('en', config('app.fallback_locale'));
        $this->get('/login')->assertOk()->assertSee('lang="en"', false)->assertSee('Sign in to Message Space');
        $this->assertFileExists(public_path('assets/message-space.svg'));
        $this->assertFileExists(public_path('downloads/sample-broadcast.csv'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('user.whatsapp.templates.create'))->assertOk()
            ->assertViewHas('template', fn ($template) => $template->language === 'en')
            ->assertSee('Message body')->assertSee('Choose emoji');
        $this->post(route('user.whatsapp.templates.store'), [])->assertSessionHasErrors('name')
            ->assertSessionHas('errors', fn ($errors) => $errors->first('name') === 'Template name is required.');
    }

    public function test_user_and_admin_pages_render_in_english(): void
    {
        $user = User::factory()->create(['name' => 'Example User']);
        $this->actingAs($user);
        foreach (['user.dashboard', 'user.wallet', 'user.whatsapp.templates.index', 'user.whatsapp.broadcasts.index', 'user.whatsapp.reports.index'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('lang="en"', false)->assertSee('Balance &amp; Transactions', false);
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['admin.dashboard', 'admin.users.index', 'admin.users.create', 'admin.whatsapp.templates.index', 'admin.whatsapp.broadcasts.index', 'admin.whatsapp.reports.index'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('lang="en"', false)->assertSee('Template Approvals');
        }
        $this->get(route('admin.users.wallet', $user))->assertOk()->assertSee('Add funds')->assertSee('IDR 500');
    }
}
