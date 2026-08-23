<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Grado;
use Illuminate\Database\Eloquent\Factories\Factory;

class PensumFactory extends Factory
{
    protected $model = \App\Models\Pensum::class;

    public function definition(): array
    {
        return [
            'id_carrera' => Carrera::factory(),
            'id_curso' => Curso::factory(),
            'id_grado' => Grado::factory(),
            'obligatorio' => true,
        ];
    }
}
