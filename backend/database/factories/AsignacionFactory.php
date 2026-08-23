<?php

namespace Database\Factories;

use App\Models\Aula;
use App\Models\Catedratico;
use App\Models\Curso;
use App\Models\PeriodoAcademico;
use Illuminate\Database\Eloquent\Factories\Factory;

class AsignacionFactory extends Factory
{
    protected $model = \App\Models\Asignacion::class;

    public function definition(): array
    {
        return [
            'id_catedratico' => Catedratico::factory(),
            'id_curso' => Curso::factory(),
            'id_aula' => Aula::factory(),
            'id_periodo' => PeriodoAcademico::factory(),
        ];
    }
}
