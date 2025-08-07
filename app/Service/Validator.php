<?php

declare(strict_types=1);

namespace App\Service;

class Validator
{
    public function __construct(public readonly Response $response) {}

    public function validateBody(mixed $body): void
    {
        if (empty($body['fen']) || !is_string($body['fen'])) {
            $this->response->send(message: 'Error: Incorrect request - 1');
        }

        if ((strlen($body['fen']) < 10) || (strlen($body['fen'])) > 200) {
            $this->response->send(message: 'Error: Incorrect fen.');
        }

        if (!empty($body['move'])) {
            if (!is_string($body['move'])) {
                $this->response->send(message: 'Error: Incorrect move. 1');
            }

            if ((strlen($body['move']) < 2) || (strlen($body['move']) > 5)) {
                $this->response->send(message: 'Error: Incorrect move. 2');
            }
        }

        if (!empty($body['legalMoves'])) {
            if (!is_array($body['legalMoves'])) {
                $this->response->send(message: 'Error: Incorrect legalMoves');
            }
        }

        return;
    }

    public function checkJsonRequest(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        if (str_contains($contentType, 'application/json') || str_contains($accept, 'application/json')) {
            return;
        }

        $this->response->send(message: 'Error: Must be a JSON request.');
    }
}
