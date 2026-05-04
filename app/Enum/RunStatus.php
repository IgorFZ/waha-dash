<?php

namespace App\Enums;

enum RunStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Success => 'Enviado',
            self::Failed => 'Falhou',
            self::Skipped => 'Ignorado',
        };
    }
}
