<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SecurePassword implements ValidationRule
{
    /**
     * Valida: mínimo 8 caracteres y al menos una letra y un número.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || strlen($value) < 8) {
            $fail('La contraseña debe tener al menos 8 caracteres.');

            return;
        }

        if (!preg_match('/[A-Za-z]/', $value) || !preg_match('/[0-9]/', $value)) {
            $fail('La contraseña debe contener al menos una letra y un número.');
        }
    }
}
