<?php

namespace Database\Factories;

use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Factories\Factory;

class AsistenciaFactory extends Factory
{
    protected $model = \App\Models\Asistencia::class;

    public function definition(): array
    {
        return [
            'id_inscripcion' => Inscripcion::factory(),
            'fecha' => $this->faker->date(),
            'estado' => $this->faker->randomElement(['presente', 'ausente', 'tarde']),
        ];
    }
}
