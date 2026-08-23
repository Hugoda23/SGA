<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\Asignacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class InscripcionFactory extends Factory
{
    protected $model = \App\Models\Inscripcion::class;

    public function definition(): array
    {
        return [
            'id_alumno' => Alumno::factory(),
            'id_asignacion' => Asignacion::factory(),
            'fecha_inscripcion' => now()->toDateString(),
            'estado' => 'activo',
        ];
    }

    public function retirado(): static
    {
        return $this->state(fn () => [
            'estado' => 'retirado',
            'fecha_retiro' => now()->toDateString(),
        ]);
    }
}
