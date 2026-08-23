<?php

namespace Database\Factories;

use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalificacionFinalFactory extends Factory
{
    protected $model = \App\Models\CalificacionFinal::class;

    public function definition(): array
    {
        return [
            'id_inscripcion' => Inscripcion::factory(),
            'nota_final' => $this->faker->numberBetween(60, 100),
        ];
    }
}
