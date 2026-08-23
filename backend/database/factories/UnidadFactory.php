<?php

namespace Database\Factories;

use App\Models\Asignacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnidadFactory extends Factory
{
    protected $model = \App\Models\Unidad::class;

    public function definition(): array
    {
        return [
            'id_asignacion' => Asignacion::factory(),
            'numero_semana' => $this->faker->numberBetween(1, 20),
            'titulo' => $this->faker->sentence(3),
            'estado' => 'planificado',
        ];
    }
}
