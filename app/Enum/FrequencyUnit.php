<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum FrequencyUnit: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Semanal',
            self::Monthly => 'Mensal',
            self::Yearly => 'Anual',
        };
    }

    /**
     * Avança a data em N unidades desta frequência.
     * (ex: 31/jan + 1 mês = 28/fev).
     */
    public function advance(CarbonInterface $date, int $interval = 1): CarbonInterface
    {
        return match ($this) {
            self::Weekly => $date->copy()->addWeeks($interval),
            self::Monthly => $date->copy()->addMonthsNoOverflow($interval),
            self::Yearly => $date->copy()->addYearsNoOverflow($interval),
        };
    }

    /**
     * Descrição legível
     */
    public function describe(int $interval = 1): string
    {
        if ($interval === 1) {
            return $this->label();
        }

        return match ($this) {
            self::Weekly => "a cada {$interval} semanas",
            self::Monthly => "a cada {$interval} meses",
            self::Yearly => "a cada {$interval} anos",
        };
    }
}
