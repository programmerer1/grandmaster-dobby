<?php

declare(strict_types=1);

namespace App\Service;

use \DateTimeImmutable;

class PromtAction
{
    public readonly DateTimeImmutable $date;
    public readonly Response $response;
    public readonly FireworksApi $fireworksApi;
    public readonly Env $env;

    public function __construct(Response $response, FireworksApi $fireworksApi, Env $env)
    {
        $this->date = new DateTimeImmutable('now');
        $this->response = $response;
        $this->fireworksApi = $fireworksApi;
        $this->env = $env;
    }

    public function processingPromtCommand(array $body)
    {
        $answer = $this->fireworksApi->send($this->env->env['DEFAULT_API_KEY'], $body);
        $this->response->send(message: $answer);
    }
}
