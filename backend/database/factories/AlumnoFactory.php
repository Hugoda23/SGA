<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumnoFactory extends Factory
{
    protected $model = \App\Models\Alumno::class;

    public function definition(): array
    {
        return [
            'id_usuario' => Usuario::factory(),
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'codigo_mineduc' => $this->faker->unique()->numerify('MIN-#######'),
            'correo' => $this->faker->unique()->safeEmail(),
            'telefono' => $this->faker->numerify('########'),
            'genero' => $this->faker->randomElement(['masculino', 'femenino']),
            'nacionalidad' => 'Guatemalteca',
            'tipo_documento' => 'cui',
            'numero_documento' => $this->faker->unique()->numerify('#############'),
            'id_carrera' => Carrera::factory(),
            'estado_academico' => 'activo',
        ];
    }
}
