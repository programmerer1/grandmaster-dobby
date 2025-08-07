<?php

declare(strict_types=1);

namespace App\Model;

use App\Service\Db;
use App\Service\ServicesExceptionHandler;
use \Throwable;

class Model
{
    public function __construct(public readonly Db $db, public readonly ServicesExceptionHandler $servicesExceptionHandler) {}
}
