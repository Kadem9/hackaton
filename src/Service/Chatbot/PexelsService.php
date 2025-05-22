<?php

// src/Service/PexelsService.php
namespace App\Service\Chatbot;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PexelsService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function searchImages(string $query, int $perPage = 3): array
    {
        $response = $this->client->request('GET', 'https://api.pexels.com/v1/search', [
            'headers' => ['Authorization' => $this->apiKey],
            'query' => ['query' => $query . ' outdoor', 'per_page' => $perPage * 3], // on élargit les résultats pour pouvoir filtrer ensuite
        ]);
    
        $data = $response->toArray();
    
        $photos = array_filter($data['photos'] ?? [], function ($photo) {
            return $photo['width'] > $photo['height']; // format paysage uniquement
        });
    
        // On limite à $perPage après filtrage
        $photos = array_slice($photos, 0, $perPage);
    
        return array_map(fn($photo) => $photo['src']['medium'], $photos);
    }
    
}