<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique();
        });

        // Preserve existing accounts and resolve duplicate email prefixes deterministically.
        DB::table('users')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $base = substr(trim(preg_replace('/[^a-z0-9._-]/', '', Str::lower(Str::ascii(Str::before($user->email, '@')))), '._-'), 0, 40);
                if (strlen($base) < 3) {
                    $base = 'user'.$user->id;
                }

                $username = $base;
                $suffix = 1;
                while (DB::table('users')->where('username', $username)->exists()) {
                    $username = $base.'.'.$suffix++;
                }

                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
