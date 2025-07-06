<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SemanticTurkeyUser>
 */
class SemanticTurkeyUserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'has_account' => true,
            'is_active' => false,
            'password' => 'admin',
        ];
    }
}
