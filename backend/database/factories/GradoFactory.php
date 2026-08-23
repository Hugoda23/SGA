<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GradoFactory extends Factory
{
    protected $model = \App\Models\Grado::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->numerify('Grado ##'),
            'nivel' => $this->faker->randomElement(['Primaria', 'Básico', 'Diversificado']),
        ];
    }
}
