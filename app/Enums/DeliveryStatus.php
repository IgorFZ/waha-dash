<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Enviado',
            self::Delivered => 'Entregue',
            self::Read => 'Lido',
            self::Failed => 'Falhou',
        };
    }
}
