<?php

namespace Database\Factories;

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
            'role' => 'anggota',
            'member_code' => 'MBR-' . fake()->unique()->numerify('#####'),
            'phone' => fake()->phoneNumber(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * State untuk role Admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'member_code' => null,
        ]);
    }

    /**
     * State untuk role Petugas.
     */
    public function petugas(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'petugas',
            'member_code' => null,
        ]);
    }

    /**
     * State untuk role Anggota.
     */
    public function anggota(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'anggota',
            'member_code' => 'MBR-' . fake()->unique()->numerify('#####'),
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
