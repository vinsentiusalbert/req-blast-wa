<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_pages_are_available(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk akun ShopAds');
        $this->get('/register')->assertOk()->assertSee('Daftar Akun ShopAds');
    }

    public function test_guests_are_redirected_to_login_for_protected_pages(): void
    {
        foreach (['/dashboard', '/admin/dashboard', '/admin/users', '/user/dashboard'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_registration_cannot_assign_admin_role(): void
    {
        $this->post('/register', [
            'name' => 'New User',
            'username' => ' NEW.USER ',
            'email' => 'NEW@example.com',
            'password' => 'Secure123!',
            'password_confirmation' => 'Secure123!',
            'role' => 'admin',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame('user', $user->role);
        $this->assertSame('new.user', $user->username);
        $this->assertTrue(Hash::check('Secure123!', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_registration_validates_duplicate_email_and_password_confirmation(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', [
            'name' => 'New User', 'email' => 'TAKEN@example.com',
            'username' => 'new.user',
            'password' => 'Secure123!', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['email', 'password']);

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_users_can_login_and_logout(): void
    {
        $user = User::factory()->create(['username' => 'creative.user', 'password' => 'Secure123!']);
        $this->post('/login', ['username' => ' CREATIVE.USER ', 'password' => 'Secure123!', 'remember' => 1])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertRedirect('/user/dashboard');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/user/dashboard')->assertRedirect('/login');
    }

    public function test_invalid_password_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['username' => $user->username, 'password' => 'wrong'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['password' => 'Secure123!']);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['username' => strtoupper($user->username), 'password' => 'wrong']);
        }

        $this->post('/login', ['username' => $user->username, 'password' => 'Secure123!'])
            ->assertSessionHasErrors('username')
            ->assertSessionHas('errors', fn ($errors) => str_contains($errors->first('username'), 'Terlalu banyak percobaan'));
        $this->assertGuest();
    }

    public function test_authenticated_users_cannot_access_guest_forms(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/login')->assertRedirect('/dashboard');
        $this->get('/register')->assertRedirect('/dashboard');
    }

    public function test_email_cannot_be_used_instead_of_username(): void
    {
        $user = User::factory()->create(['password' => 'Secure123!']);
        $this->post('/login', ['email' => $user->email, 'password' => 'Secure123!'])
            ->assertSessionHasErrors('username');
        $this->post('/login', ['username' => $user->email, 'password' => 'Secure123!'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_registration_rejects_duplicate_and_invalid_usernames(): void
    {
        User::factory()->create(['username' => 'creative.team']);
        foreach (['CREATIVE.TEAM', 'ab', 'has spaces', 'user@example.com', '.leading', str_repeat('a', 51), ['invalid']] as $username) {
            $this->post('/register', [
                'name' => 'New User', 'username' => $username, 'email' => 'new@example.com',
                'password' => 'Secure123!', 'password_confirmation' => 'Secure123!',
            ])->assertSessionHasErrors('username');
            // Each invalid registration is independent of the route-level rate limit.
            $this->app['cache']->flush();
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_demo_accounts_can_login_by_username(): void
    {
        $this->seed();
        foreach (['admin' => 'Admin123!', 'user' => 'User12345!'] as $username => $password) {
            $this->post('/login', compact('username', 'password'))->assertRedirect('/dashboard');
            $this->get('/dashboard')->assertRedirect('/'.$username.'/dashboard');
            $this->post('/logout')->assertRedirect('/login');
        }
    }
}
