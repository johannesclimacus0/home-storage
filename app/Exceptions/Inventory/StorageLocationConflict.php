<?php

declare(strict_types=1);

namespace App\Exceptions\Inventory;

use DomainException;

final class StorageLocationConflict extends DomainException {}
