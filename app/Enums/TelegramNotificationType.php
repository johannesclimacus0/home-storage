<?php

namespace App\Enums;

enum TelegramNotificationType: string
{
    case LowStock = 'low_stock';

    public function label(): string
    {
        return match ($this) {
            self::LowStock => 'Заканчивающиеся продукты',
        };
    }
}
