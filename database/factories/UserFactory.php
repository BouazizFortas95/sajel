<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Teacher->value,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * An administrator account (the platform manager).
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::Admin->value,
        ]);
    }

    /**
     * A teacher account. Subject specialization is assigned by the seeder,
     * because it depends on the subjects that already exist in the database.
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::Teacher->value,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
