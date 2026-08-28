<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first
        $this->call(RolesTableSeeder::class);

        // Create admin user if doesn't exist
        if (!User::where('email', 'admin@example.com')->exists()) {
            $user = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
            ]);

            // Assign admin role to user
            $adminRole = DB::table('roles')->where('name', 'admin')->first();
            if ($adminRole) {
                DB::table('role_user')->insert([
                    'user_id' => $user->id,
                    'role_id' => $adminRole->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
