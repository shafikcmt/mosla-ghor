<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            'name'     => 'Admin',
            'password' => 'password',
        ];

        // Only set columns that actually exist on the users table, so the
        // seeder works regardless of which migrations have been applied.
        if (Schema::hasColumn('users', 'is_admin')) {
            $attributes['is_admin'] = true;
        }
        if (Schema::hasColumn('users', 'role')) {
            $attributes['role'] = 'admin';
        }
        if (Schema::hasColumn('users', 'phone')) {
            $attributes['phone'] = '01700000000';
        }
        if (Schema::hasColumn('users', 'status')) {
            $attributes['status'] = 'active';
        }

        User::updateOrCreate(
            ['email' => 'admin@mosla.test'],
            $attributes
        );
    }
}
