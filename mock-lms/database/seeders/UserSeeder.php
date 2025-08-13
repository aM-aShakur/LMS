<?php

namespace Database\Seeders;

use App\Modules\Core\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'email'      => 'admin@example.com',
            'password'   => Hash::make('password'),
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'status'     => 'active',
        ]);

        User::factory(10)->create();
    }
}
