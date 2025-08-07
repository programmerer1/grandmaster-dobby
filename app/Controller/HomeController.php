<?php

declare(strict_types=1);

namespace App\Controller;

use AttributeRouter\Route;
use App\Service\Env;
use App\Service\PromtAction;
use App\Service\Validator;
use App\Service\View;

class HomeController
{
    private $body;

    public function __construct(
        public readonly Env $env,
        public readonly Validator $validator,
        public readonly PromtAction $promtAction
    ) {}

    #[Route(path: '', methods: ['GET'], name: 'home')]
    public function index(View $view)
    {
        header('Content-Type:text/html; charset=UTF-8');
        $view->render('home');
    }

    #[Route(path: '/api', methods: ['POST'], name: 'api')]
    public function api()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->validator->checkJsonRequest();
        $this->body = file_get_contents('php://input');
        $this->body = json_decode($this->body, true);
        $this->validator->validateBody($this->body);
        $this->promtAction->processingPromtCommand($this->body);
    }
}
