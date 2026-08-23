<?php

namespace Database\Factories;

use App\Models\Asignacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class HorarioDetalleFactory extends Factory
{
    protected $model = \App\Models\HorarioDetalle::class;

    public function definition(): array
    {
        return [
            'id_asignacion' => Asignacion::factory(),
            'dia_semana' => 'lunes',
            'hora_inicio' => '08:00:00',
            'hora_fin' => '09:00:00',
        ];
    }
}
