<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(2)->create();

        // Usuário com saldo negativo
        User::factory()->create([
            'firstName'   => fake()->firstName(),
            'lastName'    => fake()->lastName(),
            'email'       => fake()->unique()->safeEmail(),
            'password'    => Hash::make('password'),
            'balance'     => -100
        ]);
    }
}
