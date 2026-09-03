<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

enum TelegramReminderFrequency: string
{
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function nextAfter(CarbonImmutable $date): CarbonImmutable
    {
        return match ($this) {
            self::Hourly => $date->addHour(),
            self::Daily => $date->addDay(),
            self::Weekly => $date->addWeek(),
            self::Monthly => $date->addMonthNoOverflow(),
        };
    }
}
