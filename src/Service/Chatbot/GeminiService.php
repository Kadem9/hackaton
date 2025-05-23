<?php
// src/Service/Chatbot/GeminiService.php

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
        $prompt .= "\nQuelles opérations recommandez-vous ? Répondez uniquement avec les noms exacts de la liste ci-dessus, ou 'Passage au banc de diagnostic' si rien ne correspond. Peu importe la réponse je veux que 'Passage au banc de diagnostic' soit présent";

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

  public function getMaintenanceRecommendations(array $vehicle): array
{
    $prompt = <<<PROMPT
Tu es un expert en entretien automobile.
Donne une liste de recommandations d'entretien pour :

- Marque : {$vehicle['brand']}
- Modèle : {$vehicle['model']}
- Kilométrage actuel : {$vehicle['mileage']} km
- Année de mise en circulation : {$vehicle['circulation_year']}
- Dernière visite : {$vehicle['last_visit']}

Réponds uniquement en JSON avec ce format :
[
  { "label": "Vidange moteur", "type": "km", "in": 1000 },
  { "label": "Contrôle freins", "type": "jours", "in": 43 }
]

Pas de texte libre. Pas de remarques. Aucune balise markdown comme ```json.
PROMPT;

    $response = $this->client->request('POST', 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent', [
        'query' => ['key' => $this->geminiApiKey],
        'json' => [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]]
            ]]
        ]
    ]);

    $result = $response->toArray(false);
    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

    // 🧼 Clean markdown ```
    $clean = trim($text);
    $clean = preg_replace('/^```json|^```|```$/m', '', $clean); // remove ```json or ```
    $clean = trim($clean);

    try {
        return json_decode($clean, true, 512, JSON_THROW_ON_ERROR);
    } catch (\Throwable $e) {
        return []; // fallback if bad response
    }
}


}
