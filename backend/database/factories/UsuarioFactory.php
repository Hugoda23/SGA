<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuarioFactory extends Factory
{
    protected $model = \App\Models\Usuario::class;

    public function definition(): array
    {
        return [
            'username' => Str::slug($this->faker->unique()->userName()),
            'password' => Hash::make('Password1'),
            'estado' => 'activo',
            'password_change_required' => false,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['estado' => 'inactivo']);
    }
}
