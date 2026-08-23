<?php

namespace Database\Factories;

use App\Models\Asignacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluacionFactory extends Factory
{
    protected $model = \App\Models\Evaluacion::class;

    public function definition(): array
    {
        return [
            'id_asignacion' => Asignacion::factory(),
            'id_zona' => null,
            'unidad_academica' => 1,
            'nombre' => $this->faker->words(2, true),
            'porcentaje' => 10,
        ];
    }
}
