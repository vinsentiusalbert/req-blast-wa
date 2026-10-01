<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsernameMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_accounts_receive_unique_usernames_without_changing_credentials(): void
    {
        $migration = require database_path('migrations/2026_09_18_000000_add_username_to_users_table.php');
        $migration->down();
        $password = Hash::make('Existing123!');
        foreach (['admin@example.com', 'admin@agency.com', 'ADMIN@other.com', 'a@example.com', 'admin.1@example.com'] as $email) {
            DB::table('users')->insert([
                'name' => 'Existing User', 'email' => $email, 'password' => $password, 'role' => 'admin',
            ]);
        }
        $migration->up();

        $users = DB::table('users')->orderBy('id')->get();
        $this->assertSame(['admin', 'admin.1', 'admin.2', 'user4', 'admin.1.1'], $users->pluck('username')->all());
        foreach ($users as $user) {
            $this->assertSame($password, $user->password);
            $this->assertSame('admin', $user->role);
        }
        $this->post('/login', ['username' => 'admin.1', 'password' => 'Existing123!'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(User::findOrFail(2));
    }
}
