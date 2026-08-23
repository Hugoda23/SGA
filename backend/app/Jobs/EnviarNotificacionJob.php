<?php

namespace App\Jobs;

use App\Models\Usuario;
use App\Services\NotificacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnviarNotificacionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Usuario $usuario,
        public string $mensaje
    ) {}

    public function handle(): void
    {
        NotificacionService::crear($this->usuario, $this->mensaje);
    }
}
