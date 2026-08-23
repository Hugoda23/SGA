<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CursoFactory extends Factory
{
    protected $model = \App\Models\Curso::class;

    public function definition(): array
    {
        return [
            'nombre_curso' => $this->faker->unique()->words(3, true),
            'descripcion' => $this->faker->sentence(),
            'creditos' => $this->faker->numberBetween(2, 5),
        ];
    }
}
