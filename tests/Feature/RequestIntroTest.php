<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestIntroTest extends TestCase
{
    use RefreshDatabase;

    public function test_intro_opens_after_login_once_and_can_be_reopened(): void
    {
        $user = User::factory()->create(['password' => 'Password123!']);
        $this->post(route('login'), ['username' => $user->username, 'password' => 'Password123!'])
            ->assertRedirect(route('dashboard'))->assertSessionHas('show_wa_request_intro', true);
        $this->get(route('dashboard'))->assertRedirect(route('user.dashboard'));
        $this->get(route('user.dashboard'))->assertOk()->assertSee('Request WA Long Number')
            ->assertSee('data-auto-open="true"', false)->assertSee('Panduan request');
        $this->get(route('user.dashboard'))->assertOk()->assertSee('data-auto-open="false"', false);
        $this->post(route('logout'));
        $this->post(route('login'), ['username' => $user->username, 'password' => 'wrong'])
            ->assertSessionHasErrors('username')->assertSessionMissing('show_wa_request_intro');
    }

    public function test_admin_intro_and_request_queue_are_available(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'password' => 'Password123!']);
        $this->post(route('login'), ['username' => $admin->username, 'password' => 'Password123!']);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('data-auto-open="true"', false)
            ->assertSee('Sebagai admin')->assertSee('Campaign menunggu review')
            ->assertSee(route('admin.whatsapp.broadcasts.index', ['status' => 'pending']));
    }
}
