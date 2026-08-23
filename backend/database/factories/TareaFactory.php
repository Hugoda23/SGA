<?php

namespace Database\Factories;

use App\Models\Asignacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class TareaFactory extends Factory
{
    protected $model = \App\Models\Tarea::class;

    public function definition(): array
    {
        return [
            'id_asignacion' => Asignacion::factory(),
            'titulo' => $this->faker->sentence(3),
            'descripcion' => $this->faker->sentence(),
            'puntos' => null,
            'fecha_entrega' => now()->addDays(7),
            'permitir_link' => false,
        ];
    }
}
