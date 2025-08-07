<?php

declare(strict_types=1);

namespace App\Service;

use \DateTimeImmutable;
use App\Model\Model;

class PromtAction
{
    public readonly DateTimeImmutable $date;
    public readonly Model $model;
    public readonly Response $response;
    public readonly FireworksApi $fireworksApi;
    public readonly Env $env;

    public function __construct(Model $model, Response $response, FireworksApi $fireworksApi, Env $env)
    {
        $this->model = $model;
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
