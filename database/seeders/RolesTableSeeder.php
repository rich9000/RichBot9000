<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            ['name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'user', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'manager', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'support', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'developer', 'created_at' => now(), 'updated_at' => now()], // For development tools access
            ['name' => 'guest', 'created_at' => now(), 'updated_at' => now()], // For limited access
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert($role);
        }
    }
} 