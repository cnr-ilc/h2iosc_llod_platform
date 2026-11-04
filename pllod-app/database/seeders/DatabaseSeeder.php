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
        Role::firstOrCreate(['name' => UserRole::SUPER_ADMIN->value]);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
        Role::firstOrCreate(['name' => UserRole::USER->value]);

        // Users - using withoutEvents to prevent job dispatching
        User::withoutEvents(function () {
            $superAdmin = User::firstOrCreate(
                ['email' => 'superadmin@pllod.dev'],
                [
                    'name' => 'Super Admin',
                    'last_name' => 'Super Admin',
                    'password' => Hash::make('superadmin'),
                ]
            );

            if (! $superAdmin->hasRole(UserRole::SUPER_ADMIN->value)) {
                $superAdmin->assignRole(UserRole::SUPER_ADMIN->value);
            }
        });

        // Settings
        Setting::firstOrCreate(
            ['key' => 'score_calculation_guide_file_path'],
            [
                'label' => 'Score Calculation Guide File',
                'value' => '',
                'type' => 'file',
            ]
        );
    }
}
