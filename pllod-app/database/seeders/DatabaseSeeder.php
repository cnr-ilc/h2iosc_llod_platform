<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles
        Role::create(['name' => UserRole::SUPER_ADMIN->value]);
        Role::create(['name' => UserRole::ADMIN->value]);
        Role::create(['name' => UserRole::USER->value]);

        // Users
        User::factory()->createQuietly([
            'name' => 'Super Admin',
            'last_name' => 'Super Admin',
            'email' => 'superadmin@pllod.dev',
            'password' => Hash::make('superadmin'),
        ])->assignRole(UserRole::SUPER_ADMIN->value);

        // User::factory()->createQuietly([
        //     'name' => 'Admin',
        //     'last_name' => 'Admin',
        //     'email' => 'admin@pllod.dev',
        //     'password' => Hash::make('admin'),
        // ])->assignRole([UserRole::ADMIN->value, UserRole::USER->value]);

        // User::factory()->createQuietly([
        //     'name' => 'User',
        //     'last_name' => 'User',
        //     'email' => 'user@pllod.dev',
        //     'password' => Hash::make('user'),
        // ])->assignRole(UserRole::USER->value);

        // User::factory()->createQuietly([
        //     'name' => 'Rachele',
        //     'last_name' => 'Sprugnoli',
        //     'email'    => 'rachele.sprugnoli@unicatt.it',
        //     'password' => Hash::make('s3fN7nbjt8@jG7!dph#W'),
        // ])->assignRole(UserRole::USER->value);

        // Settings
        Setting::create([
            'key' => 'score_calculation_guide_file_path',
            'label' => 'Score Calculation Guide File',
            'value' => '',
            'type' => 'file',
        ]);

        Setting::create([
            'key' => 'raccommendetions_file_path',
            'label' => 'Raccommendetions File',
            'value' => '',
            'type' => 'file',
        ]);
    }
}
