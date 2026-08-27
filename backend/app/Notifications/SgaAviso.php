<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Implementa ShouldQueue para que el envío (llamada de red a cada
 * servicio push) se despache al queue-worker en vez de bloquear la
 * request HTTP que originó la notificación.
 */
class SgaAviso extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $mensaje)
    {
    }

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('SGA')
            ->icon('/pwa-icon-192.png')
            ->body($this->mensaje)
            ->data(['url' => '/notificaciones'])
            ->options(['TTL' => 3600]);
    }
}
