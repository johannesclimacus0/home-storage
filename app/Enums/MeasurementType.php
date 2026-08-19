<?php

namespace App\Enums;

enum MeasurementType: string
{
    case Mass = 'mass';
    case Volume = 'volume';
    case Count = 'count';
}
