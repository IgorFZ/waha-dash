<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Connected = 'connected';
    case Disconnected = 'disconnected';
    case QrPending = 'qr_pending';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Connected => 'Conectado',
            self::Disconnected => 'Desconectado',
            self::QrPending => 'Aguardando QR Code',
            self::Failed => 'Falha',
        };
    }
}