<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\Tarea;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntregaTareaFactory extends Factory
{
    protected $model = \App\Models\EntregaTarea::class;

    public function definition(): array
    {
        return [
            'id_tarea' => Tarea::factory(),
            'id_alumno' => Alumno::factory(),
            'link' => $this->faker->url(),
            'fecha_entrega' => now(),
            'estado' => 'entregada',
        ];
    }
}
