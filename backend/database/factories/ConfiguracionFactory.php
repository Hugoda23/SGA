<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConfiguracionFactory extends Factory
{
    protected $model = \App\Models\Configuracion::class;

    public function definition(): array
    {
        return [
            'clave' => $this->faker->unique()->word(),
            'valor' => (string) $this->faker->numberBetween(1, 100),
        ];
    }
}
