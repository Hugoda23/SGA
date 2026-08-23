<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EdificioFactory extends Factory
{
    protected $model = \App\Models\Edificio::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->streetName(),
            'ubicacion' => $this->faker->address(),
        ];
    }
}
