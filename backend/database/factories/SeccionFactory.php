<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SeccionFactory extends Factory
{
    protected $model = \App\Models\Seccion::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->randomLetter(),
        ];
    }
}
