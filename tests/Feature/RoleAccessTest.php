<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard_and_users(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/dashboard')->assertRedirect('/admin/dashboard');
        $this->get('/admin/dashboard')->assertOk()->assertSee('Pengguna terbaru');
        $this->get('/admin/users')->assertOk()->assertSee('Akun Anda sendiri');
    }

    public function test_user_cannot_read_or_modify_admin_resources(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get('/user/dashboard')->assertOk()->assertSee($user->email);
        $this->get('/admin/dashboard')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->patch('/admin/users/'.$user->id.'/role', ['role' => 'admin'])->assertForbidden();
        $this->assertSame('user', $user->fresh()->role);
    }

    public function test_admin_cannot_access_user_only_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/user/dashboard')->assertForbidden();
    }

    public function test_admin_can_promote_and_demote_another_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $this->actingAs($admin);

        foreach (['admin', 'user'] as $role) {
            $this->from('/admin/users')->patch('/admin/users/'.$user->id.'/role', ['role' => $role])
                ->assertRedirect('/admin/users')->assertSessionHas('status');
            $this->assertSame($role, $user->fresh()->role);
        }
    }

    public function test_role_change_takes_effect_for_an_existing_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch('/admin/users/'.$user->id.'/role', ['role' => 'user']);
        $this->actingAs($user->fresh())->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch('/admin/users/'.$admin->id.'/role', ['role' => 'user'])
            ->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_invalid_role_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $this->actingAs($admin)->patch('/admin/users/'.$user->id.'/role', ['role' => 'superadmin'])
            ->assertSessionHasErrors('role');
        $this->assertSame('user', $user->fresh()->role);
    }

    public function test_admin_can_search_and_paginate_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(12)->create();
        $match = User::factory()->create(['name' => 'Unique Member', 'email' => 'unique@example.com']);
        $this->actingAs($admin)->get('/admin/users?search=unique@example.com')
            ->assertOk()->assertSee($match->name)->assertDontSee($admin->email);
        $this->get('/admin/users?page=2')->assertOk()->assertSee('Sebelumnya');
        $this->get('/admin/users?search=nonexistent')->assertOk()->assertSee('Tidak ada pengguna');
    }

    public function test_demo_seeder_is_repeatable_without_resetting_accounts(): void
    {
        $this->seed();
        $user = User::where('email', 'user@example.com')->firstOrFail();
        $user->name = 'Changed';
        $user->save();
        $this->seed();
        $this->assertDatabaseCount('users', 2);
        $this->assertSame('Changed', $user->fresh()->name);
        $this->assertSame('admin', User::where('email', 'admin@example.com')->firstOrFail()->role);
    }
}
