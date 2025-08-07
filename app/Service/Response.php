<?php

declare(strict_types=1);

namespace App\Service;

class Response
{
    public function send(mixed $message): void
    {
        $data['answer'] = $message;
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
