<?php

namespace Database\Factories;

use App\Models\Edificio;
use Illuminate\Database\Eloquent\Factories\Factory;

class AulaFactory extends Factory
{
    protected $model = \App\Models\Aula::class;

    public function definition(): array
    {
        return [
            'nombre_aula' => $this->faker->unique()->bothify('Aula ##'),
            'capacidad' => 30,
            'id_edificio' => Edificio::factory(),
        ];
    }
}
