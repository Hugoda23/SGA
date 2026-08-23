<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ArchivoFactory extends Factory
{
    protected $model = \App\Models\Archivo::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->word() . '.pdf',
            'ruta' => 'archivos/' . $this->faker->uuid() . '.pdf',
            'tipo' => 'application/pdf',
            'fecha_subida' => now(),
        ];
    }
}
