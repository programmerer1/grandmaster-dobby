<?php

declare(strict_types=1);

namespace App\Service;

class View
{
    public function render($fileName)
    {
        require_once ROOT . 'app/Template/' . $fileName . '.php';
        exit;
    }
}
