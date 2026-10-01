<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo accounts are only available in local development and automated tests.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        foreach ([
            ['Administrator', 'admin', 'admin@example.com', 'Admin123!', User::ROLE_ADMIN],
            ['Demo User', 'user', 'user@example.com', 'User12345!', User::ROLE_USER],
        ] as [$name, $username, $email, $password, $role]) {
            if (User::where('email', $email)->orWhere('username', $username)->exists()) {
                continue;
            }

            $user = new User(compact('name', 'username', 'email', 'password'));
            $user->role = $role;
            $user->save();
        }
    }
}
