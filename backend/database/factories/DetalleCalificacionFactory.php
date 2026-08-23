<?php

namespace Database\Factories;

use App\Models\Evaluacion;
use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleCalificacionFactory extends Factory
{
    protected $model = \App\Models\DetalleCalificacion::class;

    public function definition(): array
    {
        return [
            'id_evaluacion' => Evaluacion::factory(),
            'id_inscripcion' => Inscripcion::factory(),
            'nota' => $this->faker->numberBetween(0, 100),
        ];
    }
}
