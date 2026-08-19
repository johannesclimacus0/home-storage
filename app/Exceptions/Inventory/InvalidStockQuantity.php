<?php

namespace App\Exceptions\Inventory;

use InvalidArgumentException;

final class InvalidStockQuantity extends InvalidArgumentException
{
    public function __construct(string $message, public readonly string $field = 'quantity')
    {
        parent::__construct($message);
    }
}
