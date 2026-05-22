<?php

namespace App\Services;

use OpenAI;

class OpenAIService
{
    protected $client;
    protected string $model;

    public function __construct()
    {
        $apiKey  = config('services.openai.key');
        $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        $this->model = config('services.openai.model', 'gpt-4o-mini');

        $this->client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($baseUrl)
            ->withHttpHeader('HTTP-Referer', 'http://localhost:5173')
            ->withHttpHeader('X-Title', 'Brem AI')
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 60]))
            ->make();
    }

    /**
     * Send a conversation history and return the assistant reply.
     *
     * @param  array  $messages  Array of ['role' => ..., 'content' => ...] pairs
     * @return string
     */
    public function chat(array $messages): string
    {
        $payload = array_merge(
            [
                [
                    'role'    => 'system',
                    'content' => 'You are a helpful AI assistant. Answer clearly and concisely. Use markdown formatting when appropriate.',
                ],
            ],
            $messages
        );

        $response = $this->client->chat()->create([
            'model'       => $this->model,
            'messages'    => $payload,
            'max_tokens'  => 2048,
            'temperature' => 0.7,
        ]);

        return $response->choices[0]->message->content ?? 'Sorry, I could not generate a response.';
    }
}
