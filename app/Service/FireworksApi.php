<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use \Throwable;

class FireworksApi
{
    public readonly string $url;
    public readonly string $modelName;
    private $httpClient;
    public readonly ServicesExceptionHandler $servicesExceptionHandler;
    public readonly Response $response;

    public function __construct(Env $env, ServicesExceptionHandler $servicesExceptionHandler, Response $response)
    {
        $this->servicesExceptionHandler = $servicesExceptionHandler;
        $this->response = $response;
        $this->httpClient = HttpClient::create();
        $this->url = $env->env['API_URL'];
        $this->modelName = $env->env['MODEL_NAME'];
    }

    public function send(string $apiKey, array $promt)
    {
        $content = [
            'fen' => $promt['fen'],
            'userMove' => !empty($promt['move']) ? $promt['move'] : null,
            'legalMoves' => !empty($promt['legalMoves']) ? $promt['legalMoves'] : [],
            'instruction' => 'Analyze the FEN position and the user\'s move (if provided). Choose a valid move from the provided legalMoves list in UCI format. Respond only with JSON: {"move": "chosen_move"}.'
        ];

        try {
            $payload = [
                'model' => $this->modelName,
                'max_tokens' => 256,
                'top_p' => 1,
                'top_k' => 40,
                'presence_penalty' => 0,
                'frequency_penalty' => 0,
                'temperature' => 0.1,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a chess AI. You receive the current position in FEN and the user's last move in UCI format. You must analyze the position and make your move in UCI format. Respond only with JSON containing the key 'move'. For example:{\"move\": \"c7c5\"}."
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode($content, JSON_UNESCAPED_UNICODE)
                    ]
                ]
            ];

            $response = $this->httpClient->request('POST', $this->url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

            return $this->formatAnswer($response->getContent());
        } catch (Throwable $e) {
            $this->servicesExceptionHandler->logAndSendResponse($e, 'api_request_error.log');
        }
    }

    public function formatAnswer(string $content): array
    {
        $data = json_decode($content, true);
        $move = json_decode($data['choices'][0]['message']['content'], true);

        if (is_null($move) || !isset($move['move'])) {
            $this->response->send(message: 'Error: Incorrect move from AI.');
        }

        return [
            'move' => $move['move']
        ];
    }
}
