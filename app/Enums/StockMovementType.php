<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Purchase = 'purchase';
    case Consumption = 'consumption';
    case Adjustment = 'adjustment';
}
