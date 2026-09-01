<?php

namespace App\Enums;

enum RecipeAvailabilityFilter: string
{
    case All = 'all';
    case Available = 'available';
    case Missing = 'missing';
}
