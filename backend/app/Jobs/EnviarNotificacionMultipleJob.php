<?php

namespace App\Jobs;

use App\Models\Usuario;
use App\Services\NotificacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnviarNotificacionMultipleJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $usuarioIds,
        public string $mensaje
    ) {}

    public function handle(): void
    {
        $usuarios = Usuario::whereIn('id_usuario', $this->usuarioIds)->get();
        NotificacionService::crearMultiple($usuarios->all(), $this->mensaje);
    }
}
