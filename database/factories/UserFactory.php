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
            'usuario' => 'u' . Str::lower(Str::random(10)),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'rol' => 'paciente',
            'activo' => true,
        ];
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

    public function administrador(): static
    {
        return $this->state(fn () => ['rol' => 'administrador']);
    }

    public function recepcionista(): static
    {
        return $this->state(fn () => ['rol' => 'recepcionista']);
    }

    public function medico(): static
    {
        return $this->state(fn () => ['rol' => 'medico']);
    }

    public function paciente(): static
    {
        return $this->state(fn () => ['rol' => 'paciente']);
    }

    /** Cuenta desactivada por el Administrador. */
    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
