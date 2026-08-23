<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodoAcademicoFactory extends Factory
{
    protected $model = \App\Models\PeriodoAcademico::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->year(),
            'fecha_inicio' => now()->subMonths(2)->toDateString(),
            'fecha_fin' => now()->addMonths(4)->toDateString(),
            'estado' => 'activo',
        ];
    }

    public function cerrado(): static
    {
        return $this->state(fn () => ['estado' => 'cerrado']);
    }
}
