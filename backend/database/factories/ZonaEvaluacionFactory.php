<?php

namespace Database\Factories;

use App\Models\Asignacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class ZonaEvaluacionFactory extends Factory
{
    protected $model = \App\Models\ZonaEvaluacion::class;

    public function definition(): array
    {
        return [
            'id_asignacion' => Asignacion::factory(),
            'nombre' => $this->faker->randomElement(['Zona 1', 'Zona 2', 'Zona 3']),
            'puntos' => 30,
            'posicion' => 0,
        ];
    }
}
