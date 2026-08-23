<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class CatedraticoFactory extends Factory
{
    protected $model = \App\Models\Catedratico::class;

    public function definition(): array
    {
        return [
            'id_usuario' => Usuario::factory(),
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'codigo' => $this->faker->unique()->bothify('CAT-####'),
            'especialidad' => $this->faker->jobTitle(),
            'correo' => $this->faker->unique()->safeEmail(),
            'telefono' => $this->faker->numerify('########'),
        ];
    }
}
