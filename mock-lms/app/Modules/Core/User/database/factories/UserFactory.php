<?php

namespace App\Modules\Core\User\Database\Factories;

use App\Modules\Core\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'email'      => $this->faker->unique()->safeEmail(),
            'password'   => Hash::make('password'),
            'first_name' => $this->faker->firstName(),
            'last_name'  => $this->faker->lastName(),
            'status'     => 'active',
        ];
    }
}
