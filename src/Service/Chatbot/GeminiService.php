<?php

namespace App\Service\Chatbot;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class GeminiService
{
    public function __construct(
        private HttpClientInterface $client,
        private OperationService $operationService,
        private string $geminiApiKey
    ) {}

    public function analyzeProblem(string $description): array
    {
        $operations = $this->operationService->getOperationsFromCsv();

        $prompt = "Voici la description du problème : \"$description\".\n";
        $prompt .= "Voici la liste des opérations possibles :\n";
        foreach ($operations as $op) {
            $prompt .= "- $op\n";
        }
        $prompt .= "\nQuelles opérations recommandez-vous ? Répondez uniquement avec les noms exacts de la liste ci-dessus, ou 'diagnostic complet' si rien ne correspond.";

        $response = $this->client->request('POST', 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent', [
            'query' => ['key' => $this->geminiApiKey],
            'json' => [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [['text' => $prompt]]
                ]]
            ]
        ]);

        $result = $response->toArray();
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return array_filter(array_map('trim', explode("\n", $text)));
    }
}
