<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativa',
            self::Paused => 'Pausada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Cancelled;
    }
}
